Feature: Author
  Background:
    Given user "test1" exists
    Given as user "test1"
    And using new dav path

  Scenario: Creating file with negative name match
    Given user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "!matches", "value": "\/\\\\.notexe$\/i"} |
    | checks-1  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "matches", "value": "\/^application\\\\\/(octet-stream\|x-ms-dos-executable\|x-msi\|x-msdos-program)$\/i"} |
    And User "test1" uploads file "data/dosexecutable.exe" to "/definitely.notexe"
    Then The webdav response should have a status code "201"
    And User "test1" uploads file "data/dosexecutable.exe" to "/definitely.exe"
    Then The webdav response should have a status code "403"
    Then User "test1" sees no files in the trashbin

