Feature: Author
  Background:
    Given user "test1" exists
    Given as user "test1"
    And using new dav path

  Scenario: with UPDATE permissions blocks upload
    Given user "admin" creates global flow with 200
      | name      | Flow with UPDATE permissions     |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | {"permissions": 2}               |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    And User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    Then The webdav response should have a status code "403"
    Then User "test1" sees no files in the trashbin

  Scenario: without UPDATE blocks updating but allows uploading, reading and deleting
    Given user "admin" creates global flow with 200
      | name      | Flow with READ and UPDATE permissions |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | {"permissions": 5}               |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    Given user "admin" creates global flow with 200
      | name      | Flow with DELETE permissions     |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | {"permissions": 8}               |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    And User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    Then The webdav response should have a status code "201"
    And User "test1" uploads file "data/textfile-2.txt" to "/foobar.txt"
    Then The webdav response should have a status code "403"
    And User "test1" deletes file "/foobar.txt"
    Then The webdav response should have a status code "204"
    When User "test1" loads file list of trashbin
    When as user "test1"
    And Downloading first trashed file
    Then The webdav response should have a status code "200"

  Scenario: masked permissions are returned
    Given user "admin" creates global flow with 200
      | name      | Flow with READ and UPDATE permissions |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | {"permissions": 5}               |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    Given user "admin" creates global flow with 200
      | name      | Flow with DELETE permissions     |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | {"permissions": 8}               |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    And User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    Then The webdav response should have a status code "201"
    When as user "test1"
    Then File "/foobar.txt" should have prop "oc:permissions" equal to "GDN"

  Scenario: no permissions should block file
    And User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    Then The webdav response should have a status code "201"
    When user "admin" creates global flow with 200
      | name      | Flow with NONE permissions       |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | {"permissions": 0}               |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    When as user "test1"
    When File "foobar.txt" in listing of folder "/" should have prop "oc:permissions" equal to ""
    When Downloading file "/foobar.txt"
    Then The webdav response should have a status code "404"

  Scenario: deny operation override permissions from other operations
    Given User "test1" deletes folder "/dir"
    Given User "test1" created a folder "/dir"
    Then The webdav response should have a status code "201"
    When User "test1" uploads file "data/textfile.txt" to "/dir/foobar.txt"
    Then The webdav response should have a status code "201"
    Given user "admin" creates global flow with 200
      | name      | Flow with READ and UPDATE permissions |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | {"permissions": 5}               |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    Given user "admin" creates global flow with 200
      | name      | Flow with DELETE permissions     |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | deny                             |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    When as user "test1"
    Then File "/dir/foobar.txt" should have prop "oc:permissions" equal to "R"
    And user "test1" should see following elements
      | /dir/foobar.txt  |
    When Downloading file "/dir/foobar.txt"
    Then The webdav response should have a status code "404"
