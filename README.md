# Hesdibi
#### Hesdibi is a file storage platform, also known as DMS (Document Management System).

It helps you stay organized with all of your digitizing paper-based documents on a daily basis.

## Requirements
* Hesdibi uses [Mailgun](https://www.mailgun.com/) mail services in order to send mail (obviously).

## Installation
1. Clone repository to your server
  * `git clone https://github.com/gaillota/Hesdibi.git`
2. Install dependencies
  * `php composer install`
3. Edit the `app/config/parameters.yml` file
4. Create the database
  * `php app/console doctrine:database:create`
5. Create the database schema
  * `php app/console doctrine:schema:update --force`
6. Create the first user (use the `super-admin` flag to make him so)
  * `php app/console fos:user:create $USERNAME $YOUR@EMAIL $PASSWORD --super-admin`

## Usage
### Login
![Login page](https://cloud.githubusercontent.com/assets/6444106/13424933/f8179194-dfa3-11e5-8795-1f936cdb7ea8.PNG)

In order to use Hesdibi, you must have an account. 

Every page is hidden behind the Symfony firewall, except for the login page.

### Manage files
//Screenshot

The core feature of Hesdibi is to host and organize your paperless documents

#### Folder options

#### Files options

### Other features

#### Send file via e-mail

#### Share file with other users

#### Generate share link (Google Photo like)

A Symfony project created on June 26, 2015, 8:25 pm.

TODO List
-----
- [x] Remove table header on sharedLinks and expend table on all width (12)
- [x] Add returning arrow on users list and folders list
- [ ] Display folder tree somehow in the folders list page
- [x] Check file size (in addition to mime-type) in js after selecting one in the modal
- [x] Display parents' folder on the folder moving page (for folders with same name)
- [x] Add returning arrow on folder moving page
- [x] Change button color on folder renaming modal
- [x] Add js confirm on folder delete page
- [x] Add folder options next to h2
- [x] Add returning arrow on email sending page
- [x] Add returning arrow on share-with page
- [x] Display error on share-with page when no simple users
- [x] Display folder tree on moving file page
- [x] Go back button on share link removing
- [x] Add fontawesome icon on alerts
- [x] Take care of the mobile interface
  1. Display search key when there's one
  2. "Results for xxx"
- [x] Check breadcrumb on all pages
  1. Folder deletion
  2. Share Link deletion
- [x] Resolve email / username login error (works on prod somehow)
- [x] //Change js preview of PDF
- [x] Use macro for dropdowns
- [x] Remove children folder when moving one
- [x] Add folder on share links page
- [x] Add redirectCorrectly method in FolderController
- [ ] Add phone book
- [ ] Create a search page (optional)
- [x] Check regular user page
- [x] Check fontawesome icons on all pages

- [ ] Build RESTful API:
  - Files API
  - [X] Retrieve files from a folder
  - [X] Download file
  - [X] Get file informations
  - [X] Get file data
  - [X] Send file via email
  - [X] Generate share link
  - [ ] Change file location
  - [ ] Rename file
  - [ ] Delete file
  - [ ] Upload file (leeeeel)
  - Folder API
  - [X] Get folder information
  - [ ] Change folder location
  - [ ] Rename folder
  - [ ] Delete folder
  - Users API
  - [X] Get user informations
  - [ ] ~~Change user password~~
  - [ ] Change username/email
  - [X] Create user
  - Share Link API
  - [X] Get shared links
