Feature: Author
  Background:
    Given user "test1" exists
    Given as user "test1"
    And using new dav path

  Scenario: Creating file is blocked
    Given user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    And User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    Then The webdav response should have a status code "403"
    Then User "test1" sees no files in the trashbin

  Scenario: Downloading file is blocked
    Given User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    And The webdav response should have a status code "201"
    And user "admin" creates global flow with 200
      | name      | Admin flow                       |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | deny                             |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName","operator":"is","value":"foobar.txt"} |
    And as user "test1"
    When Downloading file "/foobar.txt"
    Then The webdav response should have a status code "404"
    When Downloading file "/foobar.txt" with range "1-4"
    Then The webdav response should have a status code "404"
    Then User "test1" sees no files in the trashbin

  Scenario: Updating file is blocked
    Given User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    And The webdav response should have a status code "201"
    And user "admin" creates global flow with 200
      | name      | Admin flow                       |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | deny                             |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    When User "test1" uploads file "data/textfile-2.txt" to "/foobar.txt"
    Then The webdav response should have a status code "403"

  Scenario: Deleting file is blocked
    Given User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    And The webdav response should have a status code "201"
    And user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    When User "test1" deletes file "/foobar.txt"
    Then The webdav response should have a status code "403"

  Scenario: Deleting file inside a shard folder is blocked
    Given User "test1" created a folder "/subdir"
    Given User "test1" uploads file "data/textfile.txt" to "/subdir/foobar.txt"
    And The webdav response should have a status code "201"
    And user "test1" shares file "/subdir" with user "test2"
    And user "admin" creates global flow with 200
      | name      | Admin flow                       |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | deny                             |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    When User "test1" deletes file "/subdir/foobar.txt"
    Then The webdav response should have a status code "403"
