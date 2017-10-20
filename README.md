# Keboola Dropbox Writer V2
Use [Dropbox API v2](https://www.dropbox.com/developers/documentation/http/documentation) to upload files to Dropbox loaded from input mapping folders structure ie `/data/in/files` and `/data/in/tables` folders. Upload of files with `.manifest` extension is skipped.
Authorization is done by [oauth-v2](http://docs.oauthv2.apiary.io/) component.

#### Parameters
Configuration supports the following parameters
- **`mode`** - describes how to resolve conflicts of destination file names. If set to `rewrite` then the conflicted file will be rewritten otherwise new file with sequence number appended to the name will be created e.g. _my file (1)_.
- **`folder`** - destination folder name within the writer application folder e.g _mydata_


#### Sample configuration

```
{
  "storage": {
    "input": {
      "tables": [
        {
          "source": "in.c-api-tests.tomasfb",
          "destination": "blablabla.csv",
          "where_column": "",
          "where_values": [],
          "where_operator": "eq",
          "columns": []
        }
      ],
      "files": [
        {
          "tags": [
            "runId-284317412"
          ]
        }
      ]
    }
  },
  "parameters": {
    "mode": "rewrite",
    "folder": "mydata"
  },
  "authorization": {
    "oauth_api": {
      "id": "322358928"
    }
  }
}
```
