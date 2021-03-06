<?php

namespace AG\VaultBundle\Controller;


use AG\VaultBundle\Entity\File;
use AG\VaultBundle\Entity\Folder;
use AG\VaultBundle\Form\EmailType;
use AG\VaultBundle\Form\FileType;
use AG\VaultBundle\Form\FolderEditType;
use AG\VaultBundle\Form\FolderType;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class FolderController extends Controller
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Request
     */
    private $request;

    /**
     * @return array
     * @Template
     * @Secure(roles="ROLE_ADMIN")
     */
    public function indexAction()
    {
        $listFolders = $this->em->getRepository('AGVaultBundle:Folder')->findBy(array(
            'owner' => $this->getUser(),
            'parent' => null,
        ), array(
            'name' => 'ASC'
        ));

        return array(
            'listFolders' => $listFolders,
        );
    }

    /**
     * @param Folder $folder
     * @return array
     */
    public function showAction(Folder $folder = null, $slug = null)
    {
        //Fetch the research from the query if it exists
        $search = $this->request->query->get('s', null);

        //If user is not admin
        if (!$this->getUser()->hasRole('ROLE_ADMIN')) {
            //If non-admin user is somehow not in the root directory, kick the fuck outta him.
            if (null !== $folder)
                throw new AccessDeniedException('You cannot access this folder.');

            return $this->render('AGVaultBundle:Folder:showUser.html.twig', array(
                'listFiles' => $this->em->getRepository('AGVaultBundle:File')->findByAuthorizedUsers($this->getUser()),
            ));
        }

        //Check if folder is owned by user
        if (null !== $folder && $this->getUser() !== $folder->getOwner())
            throw new AccessDeniedException("You cannot access this folder.");

        //Check slug
        if (null !== $folder && $folder->getSlug() !== $slug)
            return $this->redirectToRoute('ag_vault_folder_show', array(
                'id' => $folder->getId(),
                'slug' => $folder->getSlug(),
            ), 301);

        //Create new folder and associated form in case of new folder
        $newFolder = new Folder;
        $newFolder->setParent($folder);
        $formFolder = $this->createForm(new FolderType, $newFolder);

        //Create new file and associated form in case of upload of file
        $file = new File;
        $file->setFolder($folder);
        $formFile = $this->createForm(new FileType, $file);

        //If a form has been submitted
        if ($this->request->isMethod('POST')) {
            $formFolder->handleRequest($this->request);
            $formFile->handleRequest($this->request);

            //If the folder form is valid => New folder
            if ($formFolder->isValid()) {
                $this->em->persist($newFolder);
                $this->em->flush();

                $this->addFlash('success', '<i class="fa fa-thumbs-up"></i> Folder created.');

                return $this->redirectToRoute('ag_vault_folder_show', array(
                    'id' => $newFolder->getId(),
                    'slug' => $newFolder->getSlug()
                ));
            }

            //If the file form is valid => New file
            if ($formFile->isValid()) {
                if (null !== $folder) {
                    $folder->setLastModified(new \DateTime());
                    $this->em->persist($folder);
                }
                $this->em->persist($file);
                if ($file->getFile()) {
                    $this->get('stof_doctrine_extensions.uploadable.manager')->markEntityToUpload($file, $file->getFile());
                }
                $this->em->flush();

                $this->addFlash('success', '<i class="fa fa-file-o"></i> File successfully uploaded !');

                return $this->redirectCorrectly($folder);
            }

            $this->addFlash('danger', '<i class="fa fa-times-circle"></i> An error occured.');
        }

        //If a research has been made
        if(!empty($search)) {
            $listFolders = $this->em->getRepository('AGVaultBundle:Folder')->findSearch($search, $this->getUser());

            $listFiles = $this->em->getRepository('AGVaultBundle:File')->findSearch($search, $this->getUser());
        } else {
            $listFolders = $this->em->getRepository('AGVaultBundle:Folder')->findBy(array(
                'parent' => $folder,
                'owner' => $this->getUser(),
            ), array(
                'name' => 'ASC'
            ));
            $listFiles = $this->em->getRepository('AGVaultBundle:File')->findBy(array(
                'folder' => $folder,
                'owner' => $this->getUser(),
            ), array(
                'name' => 'ASC'
            ));
        }

        //Get the size of the current folder
        $size = null !== $folder ? $folder->getSize() : $this->em->createQuery("SELECT SUM(f.size) FROM AG\VaultBundle\Entity\File f")->getSingleScalarResult();

        return $this->render('AGVaultBundle:Folder:showAdmin.html.twig', array(
            'currentFolder' => $folder,
            'formFolder'    => $formFolder->createView(),
            'formFile'      => $formFile->createView(),
            'listFolders'   => $listFolders,
            'listFiles'     => $listFiles,
            'search'        => $search,
            'size'          => $size,
        ));
    }

    /**
     * @param Folder $folder
     * @return array
     * @Template
     * @Secure(roles="ROLE_ADMIN")
     */
    public function removeAction(Folder $folder)
    {
        if ($this->getUser() !== $folder->getOwner())
            throw new AccessDeniedException("This folder does not belong to you.");

        $form = $this->createFormBuilder()->getForm();

        if ($form->handleRequest($this->request)->isValid()) {
            $params = $this->request->request->all();
            if ($params['keepordelete'] == "keep") {
                foreach ($folder->getFiles() as $file) {
                    $file->setFolder(null);
                    $this->em->persist($file);
                }
                foreach ($folder->getChildren() as $child) {
                    $child->setParent(null);
                    $this->em->persist($child);
                }
                $this->em->flush();
            }
            else {
                foreach ($folder->getFiles() as $file) {
                    unlink($file->getAbsolutePath());
                    $this->em->remove($file);
                }
                $this->em->flush();
            }

            $this->em->remove($folder);
            $this->em->flush();

            $this->addFlash('danger', '<i class="fa fa-trash"></i> Folder removed !');

            return $this->redirectToRoute('ag_vault_homepage');
        }

        //Retrieve the list of every parents for the current folder
        $listParents = array();
        if (null !== $folder->getParent()) {
            $nextParent = $folder->getParent();
            while (null !== $nextParent):
                $listParents[] = $nextParent;
                $nextParent = $nextParent->getParent();
            endwhile;
            $listParents = array_reverse($listParents);
        }

        return array(
            'form' => $form->createView(),
            'folder' => $folder,
            'listParents' => $listParents,
        );
    }

    /**
     * @param Folder $folder
     * @return JsonResponse
     * @Secure(roles="ROLE_ADMIN")
     */
    public function renameAction(Folder $folder)
    {
        if ($this->getUser() !== $folder->getOwner())
            throw new AccessDeniedException("This folder does not belong to you.");

        $name = $this->request->query->get('name', null);

        $response = array(
            'success' => 0,
        );

        if (null !== $name) {
            $folder->setName($name);
            $this->em->persist($folder);
            $this->em->flush();

            $response['success'] = 1;
            $response['id'] = $folder->getId();
            $response['name'] = $folder->getName();
            $response['type'] = 'folder';
            $response['route'] = $this->generateUrl('ag_vault_folder_show', array(
                'id' => $folder->getId(),
                'slug' => $folder->getSlug(),
            ), UrlGenerator::ABSOLUTE_URL);
        }

        return new JsonResponse($response);
    }

    /**
     * @param Folder $folder
     * @return array
     * @Template
     * @Secure(roles="ROLE_ADMIN")
     */
    public function moveAction(Folder $folder)
    {
        if ($this->getUser() !== $folder->getOwner())
            throw new AccessDeniedException("This folder does not belong to you.");

        $children = array();
        $nextChild = $this->em->getRepository('AGVaultBundle:Folder')->findOneBy(array(
            'parent' => $folder->getId()
        ));
        while (null !== $nextChild) {
            $children[] = $nextChild;
            $nextChild = $this->em->getRepository('AGVaultBundle:Folder')->findOneBy(array(
                'parent' => $nextChild->getId()
            ));
        }

        $form = $this->createForm(new FolderEditType($folder, $children), $folder);
        $formerFolder = $folder->getParent();

        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isValid()) {
                $this->em->persist($folder);
                $this->em->flush();

                $this->addFlash('success', '<i class="fa fa-arrows"></i> Folder moved !');

                return $this->redirectCorrectly($formerFolder);
            }

            $this->addFlash('danger', '<i class="fa fa-times-circle"></i> An error occured.');
        }

        return array(
            'folder' => $folder,
            'form' => $form->createView(),
        );
    }

    /**
     * @param Folder|null $folder
     * @return array
     * @Template
     * @Secure(roles="ROLE_ADMIN")
     */
    public function getParentsAction(Folder $folder = null)
    {
        //Retrieve the list of every parents for the current folder
        $listParents = array();
        if (null !== $folder && null !== $folder->getParent()) {
            $nextParent = $folder->getParent();
            while (null !== $nextParent):
                $listParents[] = $nextParent;
                $nextParent = $nextParent->getParent();
            endwhile;
            $listParents = array_reverse($listParents);
        }

        return array(
            'listParents' => $listParents,
        );
    }

    /**
     * Method used to redirect either in the root node or in the correct folder
     *
     * @param Folder|null $folder
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function redirectCorrectly(Folder $folder = null)
    {
        return null === $folder ?  $this->redirectToRoute('ag_vault_homepage') : $this->redirectToRoute('ag_vault_folder_show', array(
            'id' => $folder->getId(),
            'slug' => $folder->getSlug()
        ));
    }
}
