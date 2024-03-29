# User registration

You can allow users to register their accounts themselves by enabling the registrations module in `app/config/parameters.yml`

```yaml
registrations:
    enabled: true
    email_rules:
      - { self_registration: true, auto_activate: true }
```

This configuration allows self-registration and account activation for all email addresses.

## Limiting user registration by email address

Self-registration and account auto-activation can be limited
to email addresses belonging to a specific domain and/or matching a regex.

Matching is performed from top to bottom, and stops at the first matching expression,
in the same fashion the [symfony Security `access_control`](http://symfony.com/doc/current/security/access_control.html) works.

```yaml
registrations:
    enabled: true
    email_rules:
      - { regex_match: '/^.+\..+@vbgn\.be$/', self_registration: true, auto_activate: true }
      - { domain: vbgn.be, self_registration: true }
      - { self_registration: false }
```
## Automatically putting users in a group

When users self-register, they can be added to groups automatically based on their email address with the `default_groups` option.

Permissions inside authserver can also be set for the registering user with the `role` option.

```yaml
registrations:
  enabled: true
  email_rules:
    - { domain: vbgn.be, self_registration: true, default_groups: [%sysops, domain_admins], role: ROLE_SUPER_ADMIN}

## Showing a message on the registration screen

You can show a message above the registration form, which is typically used to indicate which email addresses
you can use to register on the site.

```yaml
registrations:
    registration_message: 'You can register an account with your @vbgn.be email address'
```
