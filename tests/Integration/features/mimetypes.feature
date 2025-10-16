
  Feature: Mimetype blocking
  Background:
    Given user "test1" exists
    Given as user "test1"
    And using new dav path

   Scenario: Can properly block path detected mimetypes for application/javascript
    And user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "is", "value": "application/javascript"} |
    Given User "test1" uploads file "data/code.js" to "/code.js"
    And The webdav response should have a status code "403"
    And Downloading file "/code.js" as "test1"
    And The webdav response should have a status code "404"

   # https://github.com/nextcloud/server/pull/23096
   Scenario: Can properly block path detected mimetypes for text/plain
    And user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "is", "value": "text/plain"} |
    Given User "test1" uploads file "data/code.js" to "/code.js"
    And The webdav response should have a status code "201"
    And Downloading file "/code.js" as "test1"
    And The webdav response should have a status code "200"
    Given User "test1" uploads file "data/code.js" to "/code.txt"
    And The webdav response should have a status code "403"
    And Downloading file "/code.txt" as "test1"
    And The webdav response should have a status code "404"

  Scenario: Can properly block path detected mimetypes for application/octet-stream
    And user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "is", "value": "application/octet-stream"} |
    Given User "test1" uploads file "data/hello" to "/hello"
    And The webdav response should have a status code "403"
    And Downloading file "/hello" as "test1"
    And The webdav response should have a status code "404"
    Given User "test1" uploads file "data/nc.exe" to "/nc"
    And The webdav response should have a status code "403"
    And Downloading file "/nc" as "test1"
    And The webdav response should have a status code "404"

  Scenario: Can properly block path detected mimetypes for application/x-ms-dos-executable by extension
    And user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "is", "value": "application/x-ms-dos-executable"} |
    Given User "test1" uploads file "data/nc.exe" to "/nc.exe"
    And The webdav response should have a status code "403"
    And Downloading file "/nc.exe" as "test1"
    And The webdav response should have a status code "404"

  Scenario: Blocking anything but folders still allows to rename folders
    Given User "test1" uploads file "data/hello" to "/hello"
    And user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "!is", "value": "httpd/unix-directory"} |
    Given User "test1" created a folder "/folder"
    And The webdav response should have a status code "201"
    When User "test1" moves folder "/folder" to "/folder-renamed"
    And The webdav response should have a status code "201"
    When User "test1" copies folder "/folder-renamed" to "/folder-copied"
    And The webdav response should have a status code "201"
    When User "test1" moves file "/hello" to "/hello.txt"
    And The webdav response should have a status code "403"
    When User "test1" copies file "/hello" to "/hello.txt"
    And The webdav response should have a status code "403"

  Scenario: Blocking by mimetype works in trashbin
    Given User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    And user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "!is", "value": "httpd/unix-directory"} |
    | checks-1  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "!is", "value": "text/plain"} |
    When User "test1" deletes file "/foobar.txt"
    Then The webdav response should have a status code "204"
