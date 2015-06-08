# Google Drive list

This project allows to list all file from your google drive account.
#### Requirement
* Get [composer](http://getcomposer.org), and then ```./composer.phar update```
* Create an API OAuth Customer ID on [google dev console](https://console.developers.google.com) & generate a P12 key.
* Create a `config.ini` based on `config.example.ini`

#### Work with it
```
Usage ./GoogleDriveList gdl:list -c config.ini -o result.csv [-a header]
	--configFile=|-c config.ini : config file (ini format)
	--outFile=|-o result.csv : result file (csv format)
	--additionalReturn|-a header : additional header to be return (this can be repeated several times), full list below :
			headRevisionId
			iconLink
			id
			kind
			lastModifyingUserName
			lastViewedByMeDate
			markedViewedByMeDate
			md5Checksum
			mimeType
			modifiedByMeDate
			modifiedDate
			openWithLinks
			originalFilename
			ownerNames
			quotaBytesUsed
			selfLink
			shared
			sharedWithMeDate
			thumbnailLink
			title
			version
			webContentLink
			webViewLink
			writersCanShare
```