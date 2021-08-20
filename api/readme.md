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
  - [list_tag_create](#list_tag_create)
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

### list_invite

### list_join

Join an existing list. Note that private lists require an invitation by the creator to join.

#### Accepts
```
{
  "internal_id": string,
  "token": string,
  "list_id": string
}
```

#### Returns
```
{
  "success": true (boolean),
  "message": "Successfully joined list." (string)
}
```

#### 401 Errors
- `ERR_UNAUTHORIZED` - Unable to authenticate. (May need to refresh token)

#### 400 Errors
- `ERR_INVALID_PARAMETERS` - Invalid and/or missing parameters.
- `ERR_INVALID_LIST` - List does not exist.
- `ERR_ALREADY_JOINED` - User has already joined the list.
- `ERR_NOT_INVITED` - User does not have invitation to this private list.

#### 500 Errors
- `ERR_NOT_JSON` - Unable to return valid JSON.
- `ERR_DATABASE_ACCESS` - Unable to access database.
- `ERR_TABLE_ACCESS` - Unable to access table.
- `ERR_LIST_JOIN` - User is unable to join list.

[Back to top](#table-of-contents)

### list_leave

Leave a list that user is joined to. Note that user cannot leave if they're the creator.

#### Accepts
```
{
  "internal_id": string,
  "token": string,
  "list_id": string
}
```

#### Returns
```
{
  "success": true (boolean),
  "message": "Successfully left list." (string)
}
```

#### 401 Errors
- `ERR_UNAUTHORIZED` - Unable to authenticate. (May need to refresh token)

#### 400 Errors
- `ERR_INVALID_PARAMETERS` - Invalid and/or missing parameters.
- `ERR_INVALID_LIST` - List does not exist.
- `ERR_NOT_JOINED` - User is not registered with list.
- `ERR_IS_CREATOR` - Unable to leave list; user is the creator.

#### 500 Errors
- `ERR_NOT_JSON` - Unable to return valid JSON.
- `ERR_DATABASE_ACCESS` - Unable to access database.
- `ERR_TABLE_ACCESS` - Unable to access table.
- `ERR_UNABLE_TO_LEAVE` - User is unable to leave list.

[Back to top](#table-of-contents)

### list_fetch_new

Fetch data for lists updated past last fetch date.

#### Accepts
```
{
  "internal_id": string,
  "token": string,
  "last_fetch": int
}
```

#### Returns
```
{
  "success": true (boolean),
  "lists": {
    *LIST_ID* (string): {
      "name": string,
      "description": string,
      "private": boolean,
      "creator": string,
      "creation_date": int,
      "last_modified": int,
      "tags": {
        *TAG_COLOR* (string): *TAG_NAME* (string),
        ...
      },
      ...
    }
  }
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

[Back to top](#table-of-contents)

### list_tag_create

Create a new tag for the specified list.

If a tag with the same color already exists, it will be overwritten.

_Note:_ `tag_color` *must* be a 6-character hexadecimal string

#### Accepts
```
{
  "internal_id": string,
  "token": string,
  "list_id": string,
  "tag_name": string,
  "tag_color": string
}
```

#### Returns
```
{
  "success": true (boolean),
  "message": "Successfully created new / updated tag." (string)
}
```

#### 401 Errors
- `ERR_UNAUTHORIZED` - Unable to authenticate. (May need to refresh token)

#### 400 Errors
- `ERR_INVALID_PARAMETERS` - Invalid and/or missing parameters.
- `ERR_INVALID_LIST` - List does not exist.
- `ERR_NOT_CREATOR` - Cannot create tag; you are not creator of list.

#### 500 Errors
- `ERR_NOT_JSON` - Unable to return valid JSON.
- `ERR_DATABASE_ACCESS` - Unable to access database.
- `ERR_QUERY_FAIL` - Unable to perform query.
- `ERR_LIST_MODIFY_DATE` - Unable to update list modification date.

[Back to top](#table-of-contents)

## Items

### item_create

Create a new item with the specified details.

#### Accepts
```
{
  "internal_id": string,
  "token": string,
  "list_id": string,
  "title": string,
  "notes": string ~OPTIONAL~,
  "start_date": int ~OPTIONAL~,
  "end_date": int ~OPTIONAL~,
  "duration": int ~OPTIONAL~,
  "repeat_interval": int ~OPTIONAL~,
  "remind_before": int ~OPTIONAL~,
  "priority": int ~OPTIONAL~,
  "tag": string ~OPTIONAL~
}
```

#### Returns
```
{
  "success": true (boolean),
  "message": "Successfully created item." (string)
}
```

#### 401 Errors
- `ERR_UNAUTHORIZED` - Unable to authenticate. (May need to refresh token)

#### 400 Errors
- `ERR_INVALID_PARAMETERS` - Invalid and/or missing parameters.
- `ERR_INVALID_LIST` - List does not exist.
- `ERR_NOT_CREATED` - Could not find newly created item; creation was likely unsuccessful."

#### 500 Errors
- `ERR_NOT_JSON` - Unable to return valid JSON.
- `ERR_DATABASE_ACCESS` - Unable to access database.
- `ERR_QUERY_FAIL` - Unable to perform query.

[Back to top](#table-of-contents)

### item_edit

### item_fetch_new

### item_remove
