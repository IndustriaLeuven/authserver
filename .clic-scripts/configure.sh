#!/usr/bin/env bash
set -e # Quit script on error
$CLIC application:variable:set "$CLIC_APPNAME" mysql/database --description="Name of the database" --default-existing-value
$CLIC application:variable:set "$CLIC_APPNAME" mysql/host --description="Hostname of the database" --if-not-global-exists --default-existing-value
$CLIC application:variable:set "$CLIC_APPNAME" mysql/user --description="Username to connect to the database" --if-not-global-exists --default-existing-value
$CLIC application:variable:set "$CLIC_APPNAME" mysql/password --description="Password of the database user"  --if-not-global-exists --default-existing-value
app_env=""
while [[ "$app_env" != "prod" && "$app_env" != "dev" ]]; do
    $CLIC application:variable:set "$CLIC_APPNAME" app/environment --description="Environment [prod|dev]" --default-existing-value --default=prod
    app_env="$($CLIC application:variable:get "$CLIC_APPNAME" app/environment)"
done;
mail_transport=""
while [[ "$mail_transport" != "mail" && "$mail_transport" != "smtp" && "$mail_transport" != "sendmail" && "$mail_transport" != "gmail" ]]; do
    $CLIC application:variable:set "$CLIC_APPNAME" mail/transport --description="Type of mail transport [mail|smtp|sendmail|gmail]" --if-not-global-exists --default-existing-value --default=mail
    mail_transport="$($CLIC application:variable:get "$CLIC_APPNAME" mail/transport)"
done;

if [[ "$mail_transport" != "mail" ]]; then
    $CLIC application:variable:set "$CLIC_APPNAME" mail/host --description="Hostname of the mail handler" --if-not-global-exists --default-existing-value
    $CLIC application:variable:set "$CLIC_APPNAME" mail/user --description="Username to connect to the mailhandler" --if-not-global-exists --default-existing-value
    $CLIC application:variable:set "$CLIC_APPNAME" mail/password --description="Password of the mail user" --if-not-global-exists --default-existing-value
    $CLIC application:variable:set "$CLIC_APPNAME" mail/encryption --description="Encryption type for mail [ssl|tls]" --if-not-global-exists --default-existing-value
fi;

$CLIC application:variable:set "$CLIC_APPNAME" mail/sender --description="Sender address of mails"  --default-existing-value


shib_auto_enable_user=""
while [[ "$shib_auto_enable_user" != "true" && "$shib_auto_enable_user" != "false" ]]; do
    $CLIC application:variable:set "$CLIC_APPNAME" app/shibboleth_auto_enable_user --description="Should user accounts created automatically by a shibboleth login be activated immediately? [true|false]" --default-existing-value --default=false
    shib_auto_enable_user="$($CLIC application:variable:get "$CLIC_APPNAME" app/shibboleth_auto_enable_user)"
done;

$CLIC application:variable:set "$CLIC_APPNAME" app/rollbar_token --description="Rollbar access token for this application" --default-existing-value

$CLIC application:variable:set "$CLIC_APPNAME" app/configured 1

cat > app/config/parameters-clic.yml <<EOL
parameters:
    database_driver:   pdo_mysql
    database_host:     $($CLIC application:variable:get "$CLIC_APPNAME" mysql/host --filter=json_encode)
    database_port:     ~
    database_name:     $($CLIC application:variable:get "$CLIC_APPNAME" mysql/database --filter=json_encode)
    database_user:     $($CLIC application:variable:get "$CLIC_APPNAME" mysql/user --filter=json_encode)
    database_password: $($CLIC application:variable:get "$CLIC_APPNAME" mysql/password --filter=json_encode)
    database_path:     ~

    mailer_transport:  $($CLIC application:variable:get "$CLIC_APPNAME" mail/transport --filter=json_encode)
    mailer_host:       $(if [[ "$mail_transport" != "mail" ]]; then $CLIC application:variable:get "$CLIC_APPNAME" mail/host --filter=json_encode; else echo '~'; fi)
    mailer_user:       $(if [[ "$mail_transport" != "mail" ]]; then $CLIC application:variable:get "$CLIC_APPNAME" mail/user --filter=json_encode; else echo '~'; fi)
    mailer_password:   $(if [[ "$mail_transport" != "mail" ]]; then $CLIC application:variable:get "$CLIC_APPNAME" mail/password --filter=json_encode; else echo '~'; fi)
    mailer_encryption: $(if [[ "$mail_transport" != "mail" ]]; then $CLIC application:variable:get "$CLIC_APPNAME" mail/encryption --filter=json_encode; else echo '~'; fi)
    mailer_sender:     $($CLIC application:variable:get "$CLIC_APPNAME" mail/sender --filter=json_encode)

    locale:            en
    secret:            '$(pwgen -s 100)'

    shibboleth_auto_enable_user: $($CLIC application:variable:get "$CLIC_APPNAME" app/shibboleth_auto_enable_user)
    rollbar_access_token: $($CLIC application:variable:get "$CLIC_APPNAME" app/rollbar_token --filter=json_encode)
EOL
if [[ ! -e app/config/parameters.yml ]]; then
cat > app/config/parameters.yml <<EOL
imports:
    - { resource: parameters-clic.yml }
EOL
fi
$CLIC application:execute "$CLIC_APPNAME" redeploy
