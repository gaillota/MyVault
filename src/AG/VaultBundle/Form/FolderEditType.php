<?php

namespace AG\VaultBundle\Form;


use AG\VaultBundle\Entity\Folder;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FolderEditType extends AbstractType
{
    /**
     * @var Folder $folder
     */
    private $folder;

    /**
     * @var array $children
     */
    private $children;

    public function __construct(Folder $folder, $children)
    {
        $this->folder = $folder;
        $this->children = $children;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->remove('name')
            ->remove('save')
            ->add('parent', 'entity', array(
                'class' => 'AGVaultBundle:Folder',
                'expanded' => true,
                'data_class' => 'AG\VaultBundle\Entity\Folder',
                'choice_label' => function(Folder $folder) {
                    $listParents = array(
                        $folder->getName()
                    );
                    $nextParent = $folder->getParent();
                    while(null !== $nextParent) {
                        $listParents[] = $nextParent->getName();
                        $nextParent = $nextParent->getParent();
                    }
                    $listParents = array_reverse($listParents);
                    return "Mon Vault => " . implode(" > ", $listParents);
                },
                'empty_value' => 'Mon Vault',
                'empty_data' => null,
                'required' => false,
                'label' => 'Dossier',
                'query_builder' => function(EntityRepository $er) {
                    $qb = $er->createQueryBuilder('f');

                    $qb
                        ->leftJoin('f.parent', 'p')
                        ->where('f.id != :id')
                        ->setParameter('id', $this->folder->getId())
                    ;

                    foreach ($this->children as $k => $child) {
                        $qb
                            ->andWhere("f.id != :id$k")
                            ->setParameter("id$k", $child->getId())
                        ;
                    }

                    $qb->orderBy('f.name', 'ASC');

                    return $qb;
                }
            ))
            ->add('save', 'submit', array(
                'label' => 'Enregistrer',
                'attr' => array(
                    'class' => 'pull-right btn-default'
                ),
            ))
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ag_vault_folder_edit';
    }

    /**
     * @return FolderType|null|string|\Symfony\Component\Form\FormTypeInterface
     */
    public function getParent()
    {
        return new FolderType();
    }
} 