<?php
/*************************************************************************************************************
** Title.........: PGP Class
** Version.......: 0.01a
** Author........: Rodrigo Z. Armond <rodzadra@passagemdemariana.com.br>
** Filename......: config.php
** Last changed..: 2001-10-22 
** Notes.........: This is the first alpha.
** TODO..........: Documentation, more consistents error message, more options, etc, etc, etc....
**
**************************************************************************************************************/
$GPG_BIN = "/usr/bin/gpg --no-tty --no-secmem-warning";	// This is the GNUpg Binary file
$GPG_USER_DIR = "/websites/www.distributedcity.com/gpg_keyrings/";					// This is where the users dir will be created
$GPG_PASS_LENGTH = 8;						// This is the minimum lenght accepted of the passphrase

// -- define the errors code 

// -- system errors
define(GPG_ERR1, "GNUpg binary file does not exist.\n");
define(GPG_ERR2, "GNUpg binary file is not executable.\n");
// -- check_key function errors code
define(GPG_ERR3, "The local gnupg user dir does not exist.\n");
define(GPG_ERR4, "Keys for list not found. Verify that the local gnupg user dir exist.\n");
define(GPG_ERR5, "The keyID for the recipient isn't on your keyring.\n");
// -- encrypt_message function erros code
define(GPG_ERR6, "Impossible to create the .data file. Verify that you have write access on the local gnupg user dir.\n");
define(GPG_ERR7, "Impossible to encrypt the message. Unknown error.\n");
define(GPG_ERR8, "Error when trying to encrypt the message: The header/footer of the message isn't valid.\n");
// -- decrypt_message function erros code
define(GPG_ERR9, "Error when trying to decrypt the message: The header/footer of crypted message not appear to be a valid PGP message.\n");
define(GPG_ERR10,"Impossible to create the .gpg file. Verify that you have write access on the local gnupg user dir.\n");
define(GPG_ERR11,"Impossible to read the .asc file. Verify if you have entered the correct username/password.\n");
// -- import_key function errors code
define(GPG_ERR12,"No public key file specified.\n");
define(GPG_ERR13,"This not appear to be a valid PGP public key. Error in header and/or footer.\n");
define(GPG_ERR14,"Impossible to create the .tmp file to add the key. Verify that you have write access in the local gnupg user dir.\n");
define(GPG_ERR15,"Impossible to add the public key to user keyrings. Unknown error.\n");
// -- export_key function erros code
define(GPG_ERR16,"Impossible to export the owner public key. Maybe the user can not exist.\n");
// -- remove_key function errors code
define(GPG_ERR17,"No specified public key to remove.\n");
define(GPG_ERR18,"Impossible to remove the key.\n");
// -- list_key function errors code
define(GPG_ERR19,"Impossible to list the keys.\n");
// -- gen_key function errors code
define(GPG_ERR20,"The username is empty.\n");
define(GPG_ERR21,"The email is empty.\n");
define(GPG_ERR22,"The passphrase is empty.\n");
define(GPG_ERR23,"The passphrase is too short.\n");
define(GPG_ERR24,"Impossible to create a new user dir. Verify that you have write access in the GPG dir.\n");
define(GPG_ERR25,"Impossible to crearte the local gnupg user dir. Verify that you have write acess in the GPG dir\n");
define(GPG_ERR26,"The user dir exist, please try another name.\n");
define(GPG_ERR27,"Impossible to create the temporary config file. Verify that you have write access in the GPG dir.\n");
define(GPG_ERR28,"Impossible to generate the key. Unknown error.\n");
?>
