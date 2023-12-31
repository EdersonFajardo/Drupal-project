# JWT Module

The main JWT module on its own just provides a framework for managing a
single site-wide key and integration with a JWT library. If you need
to have one or more keys that are specific for each user, see the users_jwt
sub-module and its [README documentation](modules/users_jwt/README.md).

The JWT module can used to authenticate requests from a JWT in the header, but
by default does not.

If you want to use the simple / default authentication behavior you need to
also enable the jwt_auth_consumer module.

If you want to allow users to generate a JWT at a web path (or use the API
to create a JWT with default claims), you need to also enable the
jwt_auth_issuer module.

## Site Key

Go to /admin/config/system/keys and add a JWT HMAC or RSA key.

Go to /admin/config/system/jwt to pick the key to be used.

## JWT Header and Claims

When creating a JWT to send, the iat and exp claims should always be included.

The namespaced claim "drupal / uid" is used by jwt_auth_consumer to determine
the user account to be used when authenticated. You can also use a user uuid or
username with claims "drupal / uuid" or "drupal / name". The claims are
checked in the order listed here, and the first one that's populated is
used to determine the user.

## Request Header

The JWT may be sent in either of two headers. The fallback header is intended
for use in development environments that are protected by basic authentication,
e.g. to block web crawlers.

Main header format:

    Authorization: Bearer [token]

Fallback header:

    JWT-Authorization: Bearer [token]

## API Integration

For REST api integration (e.g. Views) enable the jwt_auth authentication option.

## Including JWT Authentication in a Link

For accessing private files via direct link or certain types of API requests,
it can be useful to be able to put the JWT into the URL directly. Enable the
jwt_path_auth to enable this functionality. You need to configure it to specify
the allowed paths and include the path prefix or full filepath in the JWT
claims. The JWT must be in the "jwt" query string. This JWT must be signed
using the same side-wide key as a JWT that would be sent in the *Authorization*
header.
