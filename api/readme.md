# API Documentation

#### Table of Contents
- [API Call Flow](#typical-api-call-flow)
- [Errors](#errors)
- [Authentication](#authentication)
  - [user_register](#user_register)
  - [user_authenticate](#user_authenticate)
- [Lists](#lists)
  - [list_create](#list_create)
  - [list_edit](#list_edit)
  - [list_join](#list_join)
  - [list_leave](#list_leave)
  - [list_fetch_new](#list_fetch_new)
- [Items](#items)
  - [item_create](#item_create)
  - [item_edit](#item_edit)
  - [item_fetch_new](#item_fetch_new)
  - [item_remove](#item_remove)

## Typical API Call Flow


## Errors
API calls from `Lists` and `Items` require a temporary token obtained from `user_authenticate`.
If an invalid token is provided or the token is expired, the call will return a `401: Unauthorized` error.

If such an error is encountered, the program should call `user_authenticate` to retrieve a fresh token or require the user to sign in again (typically if authentication fails).

Other than a 401 error, the API should always return a 400 or 500 error code with following data:

```
{
  "success": false (boolean),
  "error": string,
  "message": string
}
```

Calls which return this typically indicate an error with the request or the server,
while calls that do not return this typically indicate a connection error or
a critical server malfunction.

## Authentication

### user_register

Register a new user.

#### Accepts
```
{
  "api_key": string,
  "email": string,
  "password": string
}
```

#### Returns
```
{
  "success": true (boolean),
  "message": "Successfully created user" (string)
}
```

#### 400 Errors
- `ERR_INVALID_API_KEY` - Invalid API Key.
- `ERR_INVALID_EMAIL` - Invalid Email.
- `ERR_INVALID_PASSWORD` - Invalid Password.
- `ERR_EMAIL_EXISTS` - Email already registered.

#### 500 Errors
- `ERR_NOT_JSON` - Unable to return valid JSON.
- `ERR_DATABASE_ACCESS` - Unable to access database.
- `ERR_TABLE_ACCESS` - Unable to access table.

[Back to top](#table-of-contents)

### user_authenticate

Authenticate an existing user.

If successful, an internal ID and token will be returned. These two are necessary for `Lists` and `Items` API calls.

#### Accepts
```
{
  "api_key": string,
  "email": string,
  "password": string
}
```

#### Returns
```
{
  "success": true (boolean),
  "message": "Successfully authenticated user." (string),
  "internal_id": string,
  "token": string
}
```

#### 400 Errors
- `ERR_INVALID_API_KEY` - Invalid API Key.
- `ERR_INVALID_EMAIL` - Invalid Email.
- `ERR_INVALID_PASSWORD` - Invalid Password.
- `ERR_NOT_REGISTERED` - User is not currently registered.

#### 500 Errors
- `ERR_NOT_JSON` - Unable to return valid JSON.
- `ERR_DATABASE_ACCESS` - Unable to access database.
- `ERR_TABLE_ACCESS` - Unable to access table.
- `ERR_TOKEN_VERIFICATION` - Unable to verify token.

[Back to top](#table-of-contents)

## Lists

### list_create

Create a new list with the specified values.

#### Accepts
```
{
  "internal_id": string,
  "token": string,
  "name": string,
  "description": string,
  "private": boolean
}
```

#### Returns
```
{
  "success": true (boolean),
  "message": "Successfully created list." (string)
}
```

#### 401 Errors
- `ERR_UNAUTHORIZED` - Unable to authenticate. (May need to refresh token)

#### 400 Errors
- `ERR_INVALID_PARAMETERS` - Invalid and/or missing parameters.

#### 500 Errors
- `ERR_NOT_JSON` - Unable to return valid JSON.
- `ERR_DATABASE_ACCESS` - Unable to access database.
- `ERR_TABLE_ACCESS` - Unable to access table.
- `ERR_LIST_JOIN` - List created but user is unable to join.

[Back to top](#table-of-contents)

### list_edit

Replace values from an existing list with the specified values.

#### Accepts
```
{
  "internal_id": string,
  "token": string,
  "list_id": string,
  "name": string,
  "description": string,
  "private": boolean
}
```

#### Returns
```
{
  "success": true (boolean),
  "message": "Successfully updated list." (string)
}
```

#### 401 Errors
- `ERR_UNAUTHORIZED` - Unable to authenticate. (May need to refresh token)

#### 400 Errors
- `ERR_INVALID_PARAMETERS` - Invalid and/or missing parameters.
- `ERR_INVALID_LIST` - List does not exist.
- `ERR_EDIT_NOT_PERMITTED` - User is not permitted to edit list.

#### 500 Errors
- `ERR_NOT_JSON` - Unable to return valid JSON.
- `ERR_DATABASE_ACCESS` - Unable to access database.
- `ERR_TABLE_ACCESS` - Unable to access table.

[Back to top](#table-of-contents)

### list_join

### list_leave

### list_fetch_new

## Items

### item_create

### item_edit

### item_fetch_new

### item_remove
