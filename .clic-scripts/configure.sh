#!/usr/bin/env bash
set -e # Quit script on error

$CLIC application:variable:get "$CLIC_APPNAME" app/configured >/dev/null 2>/dev/null || $CLIC application:execute "$CLIC_APPNAME" reconfigure


cat > app/config/parameters-clic.yml <<EOL
# Auto-generated by $($CLIC -V) at $(date), DO NOT EDIT.
# Run \`$CLIC application:execute "$CLIC_APPNAME" reconfigure\` to update these configuration variables.
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

if [[ $($CLIC application:variable:get "$CLIC_APPNAME" app/registration) == "y" ]]; then
    count="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/count)"
    i=1
    message="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/message --filter=json_encode)"
    if [[ "$message" == '"#"' ]]; then
        message='null'
    fi
    cat >> app/config/parameters-clic.yml <<EOL
# Registration configuration
registration:
    enabled: true
    registration_message: $message
    email_rules:
EOL

    while [[ "$i" -lt "$count" ]]; do
        regex="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/$i/regex_match --filter=json_encode)"
        if [[ "$regex" == '"*"' ]]; then
            regex='null'
        fi
        domain="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/$i/domain --filter=json_encode)"
        if [[ "$domain" == '"*"' ]]; then
            domain='null'
        fi

        self_registration="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/$i/self_registration)"
        if [[ "$self_registration" == "y" ]]; then
            self_registration='true'
        else
            self_registration='false'
        fi

        auto_activate="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/$i/auto_activate)"
        if [[ "$auto_activate" == "y" ]]; then
            auto_activate='true'
        else
            auto_activate='false'
        fi

        groups="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/$i/groups || echo ".")"
        if [[ "$groups" == "." ]]; then
            groups=""
        fi

        role="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/$i/role || echo "ROLE_USER")"
        if [[ "$role" == "ROLE_USER" ]]; then
            role_config=""
        else
            role_config=", role: $role"
        fi

        cat >> app/config/parameters-clic.yml <<EOL
        - { regex_match: ${regex}, domain: ${domain}, self_registration: $self_registration, auto_activate: $auto_activate, default_groups: [ $groups ] $role_config }
EOL
        i=$(($i+1))
    done
fi

if [[ ! -e app/config/parameters.yml ]]; then
cat > app/config/parameters.yml <<EOL
imports:
    - { resource: parameters-clic.yml }
EOL
fi

exec $CLIC application:execute "$CLIC_APPNAME" redeploy
