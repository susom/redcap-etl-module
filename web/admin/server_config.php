<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

/** @var \IU\RedCapEtlModule\RedCapEtlModule $module */

#---------------------------------------------
# Check that the user has access permission
#---------------------------------------------
$module->checkAdminPagePermission();


require_once __DIR__.'/../../dependencies/autoload.php';

use phpseclib\Crypt\RSA;
use phpseclib\Net\SCP;
use phpseclib\Net\SFTP;
use phpseclib\Net\SSH2;

use IU\RedCapEtlModule\Csrf;
use IU\RedCapEtlModule\Filter;
use IU\RedCapEtlModule\ServerConfig;
use IU\RedCapEtlModule\RedCapEtlModule;

$selfUrl      = $module->getUrl(RedCapEtlModule::SERVER_CONFIG_PAGE);
$serversUrl   = $module->getUrl(RedCapEtlModule::SERVERS_PAGE);

$submit = Filter::sanitizeButtonLabel($_POST['submit']);


#-------------------------------------------
# Get the server name
#-------------------------------------------
$serverName = Filter::sanitizeString($_POST['serverName']);
if (empty($serverName)) {
    $serverName = Filter::sanitizeString($_GET['serverName']);
    if (empty($serverName)) {
        $serverName = Filter::sanitizeString($_SESSION['serverName']);
    }
}

try {
    ServerConfig::validateName($serverName);
} catch (Exception $exception) {
    $serverName = '';
}

if (!empty($serverName)) {
    $_SESSION['serverName'] = $serverName;
}

#-------------------------------------------------------------------
# If the server name is set, get the configuration for that server
#-------------------------------------------------------------------
if (!empty($serverName)) {
    try {
        $serverConfig = $module->getServerConfig($serverName);
    } catch (Exception $exception) {
        # The server could not be found, but the most likely cause is
        # that it was deleted so don't indicate an error
        $serverConfig = null;
        $serverName = '';
        $_SESSION['serverName'] = '';
    }
}


$testOutput = '';

#------------------------------------
# Router
#------------------------------------
if (strcasecmp($submit, 'Save') === 0) {
    if (empty($serverName)) {
        $error = 'ERROR: no server name specified.';
    } else {
        try {
            $serverConfig = new ServerConfig($serverName);
            $serverConfig->set(Filter::stripTagsArrayRecursive($_POST));
            $serverConfig->validate();
            $module->setServerConfig($serverConfig);
            header('Location: '.$serversUrl);
        } catch (Exception $exception) {
            $error = 'ERROR: '.$exception->getMessage();
        }
    }
} elseif (strcasecmp($submit, 'Cancel') === 0) {
    header('Location: '.$serversUrl);
} elseif (strcasecmp($submit, 'Test Server Connection') === 0) {
    if (!isset($serverConfig)) {
        $testOutput = 'ERROR: no server configuration found.';
    } else {
        $testOutput = $serverConfig->test();
    }
}
?>



<?php #require_once APP_PATH_DOCROOT . 'ControlCenter/header.php'; ?>
<?php
#--------------------------------------------
# Include REDCap's project page header
#--------------------------------------------
ob_start();
require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';
$buffer = ob_get_clean();
$cssFile = $module->getUrl('resources/redcap-etl.css');
$link = '<link href="'.$cssFile.'" rel="stylesheet" type="text/css" media="all">';
$buffer = str_replace('</head>', "    ".$link."\n</head>", $buffer);
echo $buffer;
?>


<h4><img style="margin-right: 7px;" src="<?php echo APP_PATH_IMAGES ?>table_gear.png" alt="">REDCap-ETL Admin</h4>

<?php

$module->renderAdminPageContentHeader($serversUrl, $error, $warning, $success);
$module->renderAdminEtlServerSubTabs($selfUrl);
?>


<?php
#---------------------------------
# Server selection form
#---------------------------------
?>
<form action="<?php echo $selfUrl;?>" method="post"
      style="padding: 4px; margin-bottom: 12px; border: 1px solid #ccc; background-color: #ccc;">
    <span style="font-weight: bold;">Server:</span>
    <select name="serverName" onchange="this.form.submit()">
    <?php
    $values = $module->getServers();
    array_unshift($values, '');
    foreach ($values as $value) {
        if (strcmp($value, $serverName) === 0) {
            echo '<option value="'.Filter::escapeForHtmlAttribute($value)
                .'" selected>'.Filter::escapeForHtml($value)."</option>\n";
        } else {
            echo '<option value="'.Filter::escapeForHtmlAttribute($value).'">'
                .Filter::escapeForHtml($value)."</option>\n";
        }
    }
    ?>
    </select>
    <?php Csrf::generateFormToken(); ?>
</form>


<script>
// Show/hide Password
$(function() {
    $("#showPassword").change(function() {
        var newType = 'password';
        if ($(this).is(':checked')) {
            newType = 'text';
        }
        $("#password").each(function(){
            $("<input type='" + newType + "' style='margin: 4px;' >")
                .attr({ id: this.id, name: this.name, value: this.value, size: this.size})
                .insertBefore(this);
        }).remove();       
    })
});    

// Show/hide SSH Key Password
$(function() {
    $("#showSshKeyPassword").change(function() {
        var newType = 'password';
        if ($(this).is(':checked')) {
            newType = 'text';
        }
        $("#sshKeyPassword").each(function(){
            $("<input type='" + newType + "' style='margin: 4px;' >")
                .attr({ id: this.id, name: this.name, value: this.value, size: this.size })
                .insertBefore(this);
        }).remove();       
    })
});

$(function() {
    $("input[name=authMethod]").change(function() {
        var value = $(this).val();
        if (value == 0) {
            $("#passwordRow").hide();
            $("#sshKeyFileRow").show();
            $("#sshKeyPasswordRow").show();
        } else if (value == 1) {
            $("#passwordRow").show();
            $("#sshKeyFileRow").hide();
            $("#sshKeyPasswordRow").hide();
        }
    });
});
</script>

<?php
#----------------------------------------------------
# Server configuration form
#----------------------------------------------------
if (!empty($serverName)) {
    $authMethod = $serverConfig->getAuthMethod();
    $isActive   = $serverConfig->getIsActive();
    $activeChecked = '';
    if ($isActive) {
        $activeChecked = ' checked ';
    }
?>
<form action=<?php echo $selfUrl;?> method="post">
  <input type="hidden" name="serverName"
      value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getName());?>">
 
  <!-- ACTIVE SETTING -->
  <table style="margin-bottom: 12px;">
    <tr>
      <td style="font-weight: bold; padding-right: 6px;">Active:</td>
      <td><input type="checkbox" name="isActive" value="checked" <?php echo $activeChecked; ?> ></td>
    </tr>
  </table>
  
  
    <!-- SERVER CONNECTION SETTINGS -->
    <?php
    if (strcasecmp($serverName, ServerConfig::EMBEDDED_SERVER_NAME) !== 0) {
    ?>
        <fieldset class="server-config">
            <legend>Server Connection Settings</legend>
            <table>  
                <tr>
                    <td>Server address:</td>
                    <td><input type="text" name="serverAddress"
                        value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getServerAddress());?>"
                        size="60" style="margin: 4px;"></td>
                </tr>
    
                <tr>
                    <td style="padding-top: 4px; padding-bottom: 4px; vertical-align: top;">Authentication method:</td>
                    <td style="padding: 4px;">
                        <input type="radio" name="authMethod" id="authMethodSshKey"
                            value="<?php echo ServerConfig::AUTH_METHOD_SSH_KEY;?>"
                        <?php
                        if ($authMethod == ServerConfig::AUTH_METHOD_SSH_KEY) {
                            echo ' checked ';
                        }
                        ?>
                        style="vertical-align: middle; margin: 0;">
                        <span style="vertical-align: top; margin-right: 8px;">SSH Key</span>
                        <input type="radio" name="authMethod" id="authMethodPassword"
                            value="<?php echo ServerConfig::AUTH_METHOD_PASSWORD;?>"
                        <?php
                        if ($authMethod == ServerConfig::AUTH_METHOD_PASSWORD) {
                            echo ' checked ';
                        }
                        ?>
                        style="vertical-align: middle; margin: 0;">
                        <span style="vertical-align: top; margin-right: 8px;">Password</span>
                    </td>
                </tr>
    
                <tr>
                    <td>Username:</td>
                    <td><input type="text" name="username"
                        value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getUsername());?>"
                        size="28" style="margin: 4px;"></td>
                </tr>
    
                    <?php
                    $passwordStyle = '';
                    $sshStyle = '';
                    if ($authMethod == ServerConfig::AUTH_METHOD_PASSWORD) {
                        $sshStyle = ' style="display: none;" ';
                    } else {
                        $passwordStyle = ' style="display: none;" ';
                    }
                    ?>
    
                <tr id="passwordRow" <?php echo $passwordStyle; ?> >
                    <td>Password:</td>
                    <td>
                        <input type="password" name="password"
                            value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getPassword());?>"
                            size="28" style="margin: 4px;" id="password" autocomplete="off">
                        <input type="checkbox" id="showPassword" style="vertical-align: middle; margin: 0;">
                        <span style="vertical-align: middle;">Show</span>
                    </td>
                </tr>

      <tr id="sshKeyFileRow" <?php echo $sshStyle; ?> >
        <td>SSH key file:</td>
        <td><input type="text" name="sshKeyFile"
            value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getSshKeyFile());?>"
            size="44" style="margin: 4px;" autocomplete="off"></td>
      </tr>
      <tr id="sshKeyPasswordRow" <?php echo $sshStyle; ?> >
        <td>SSH key password:</td>
        <td>
          <input type="password" name="sshKeyPassword"
              value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getSshKeyPassword());?>"
              size="44" style="margin: 4px;" id="sshKeyPassword" autocomplete="off">
          <input type="checkbox" id="showSshKeyPassword" style="vertical-align: middle; margin: 0;">
          <span style="vertical-align: middle;">Show</span>
        </td>
      </tr>
        
    </table>
    </fieldset>
  
    <!-- SERVER COMMAND SETTINGS -->
    <fieldset class="server-config">
    <legend>Server Command Settings</legend>
    <table>    
      <tr>
        <td>Configuration directory:</td>
        <td><input type="text" name="configDir"
            value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getConfigDir());?>"
            size="60" style="margin: 4px;"></td>
      </tr>
      <tr>
        <td>ETL command prefix:</td>
        <td><input type="text" name="etlCommandPrefix"
            value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getEtlCommandPrefix());?>"
            size="60" style="margin: 4px;"></td>
      </tr>    
      <tr>
        <td>ETL command:</td>
        <td>
          <input type="text" name="etlCommand"
              value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getEtlCommand());?>"
              size="60" style="margin: 4px;">
        </td>
      </tr>
      <tr>
        <td>ETL command suffix:</td>
        <td><input type="text" name="etlCommandSuffix"
            value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getEtlCommandSuffix());?>"
            size="60" style="margin: 4px;"></td>
      </tr>
    </table>
    </fieldset>
    <?php
    } // end if not embedded server
    ?>
  
  <!-- SERVER LOGGING SETTINGS -->
  <fieldset class="server-config">
  <legend>Server Logging Settings</legend>
  <table>
    <tr>
      <td>Log file:</td>
      <td><input type="text" name="logFile"
          value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getLogFile());?>" size="60">
      </td>
    </tr>
  </table>
  </fieldset>
  
  
  <!-- SERVER E-MAIL SETTINGS -->
  <fieldset class="server-config">
  <legend>Server E-mail Settings</legend>
  <table>        
    <tr>
      <td>E-mail from address:</td>
      <td><input type="text" name="emailFromAddress"
          value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getEmailFromAddress());?>"
          size="40" style="margin: 4px;"></td>
    </tr>
    
    <tr>
      <td>Enable error e-mail:</td>
      <td>
        <?php
        $checked = '';
        if ($serverConfig->getEnableErrorEmail()) {
            $checked = ' checked ';
        }
        ?>
        <input type="checkbox" name="enableErrorEmail" value="true" <?php echo $checked; ?> >
      </td>
    </tr>
    
    <tr>
      <td>Enable summary e-mail:&nbsp;</td>
      <td>
        <?php
        $checked = '';
        if ($serverConfig->getEnableSummaryEmail()) {
            $checked = ' checked ';
        }
        ?>
        <input type="checkbox" name="enableSummaryEmail" value="true" <?php echo $checked; ?> >
      </td>
    </tr>   
       
  </table>
  </fieldset>

  
  <fieldset class="server-config">
  <legend>Database Connection SSL Settings</legend>
  <table>
    <tr>
      <td>Database SSL:</td>
      <td>
        <?php
        $checked = '';
        if ($serverConfig->getDbSsl()) {
            $checked = ' checked ';
        }
        ?>
        <input type="checkbox" name="dbSsl" value="true" <?php echo $checked; ?> >
      </td>
    </tr>
    
    <tr>
      <td style="margin-right: 1em;">Database SSL verification:&nbsp;</td>
      <td>
        <?php
        $checked = '';
        if ($serverConfig->getDbSslVerify()) {
            $checked = ' checked ';
        }
        ?>
        <input type="checkbox" name="dbSslVerify" value="true" <?php echo $checked; ?> >
      </td>
    </tr>
    
    <tr>
      <td style="margin-right: 1em;">CA certificate file:</td>
      <td><input type="text" name="caCertFile"
          value="<?php echo Filter::escapeForHtmlAttribute($serverConfig->getCaCertFile());?>" size="60">
      </td>
    </tr>
        
  </table>
  </fieldset>
  
  
  <div style="margin-top: 20px;">
    <div style="width: 50%; float: left;">
      <input type="submit" name="submit" value="Save" style="margin: auto; display: block;">
    </div>
    <div style="width: 50%; float: right;">
      <input type="submit" name="submit" value="Cancel" style="margin: auto; display: block;">
    </div>
    <div style="clear: both;">
    </div>
  </div>
  <div style="margin-top: 4ex;">
    <input type="submit" name="submit" value="Test Server Connection"> <br/>
    <textarea id="testOutput" name="testOutput" rows="4" cols="40"><?php
        echo Filter::escapeForHtml($testOutput);
    ?>&nbsp;
    </textarea>
  </div>
    <?php Csrf::generateFormToken(); ?>
</form>
<?php
}
?>

<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>
