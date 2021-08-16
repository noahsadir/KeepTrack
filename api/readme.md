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

## Authentication

### user_register

Register a new user.

#### Accepts

#### Accepts
```
{
  "member_id": string,
  "member_token": string,
  "space_id": string,
  "ignore_before": string ~optional~
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

## Lists

### list_create

### list_edit

### list_join

### list_leave

### list_fetch_new

## Items

### item_create

### item_edit

### item_fetch_new

### item_remove
