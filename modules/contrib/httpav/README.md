# HTTP Anti-virus Drupal

A Drupal module that submits uploaded files to a HTTP service endpoint. Uploaded files are sent in a configurable manner to a configurable endpoint. The response of which will control if the file can be accepted by Drupal.

The intent is to obfuscate complicated TCP/Sock Anti-virus backends with a standard HTTP interface to allow for a more microservice architecture.

## Prerequisites

1. A service that is reachable via HTTP

### API requirements

The module makes some assumptions on how the HTTP request needs to be made. It allows for parts of the request to be configurable to help accommodate differences in service endpoints.

The module will make a request that looks like:

#### Request

```
'Headers' => {
  'Content-Type' => 'multipart/form-data'
}
'multipart' => [
  [
    'name' => 'malware',
    'contents' => '@file.png',
    'filename' => 'file.png',
  ]
]
```

#### Response

The module assumes that the service will respond with the results in a particular format.

```
{
  "result": {
    "infected":true,
    "result":"ApplicUnwnt",
    "engine":"5.0.163652.1142",
    "updated":"20190406"
  }
}
```
The module expects the service to return with the `infected` key to represent if the file can be saved or not. If the `infected` key is `true` the file will fail the validation check in Drupal and will not allow the file to be saved.
