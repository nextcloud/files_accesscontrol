Feature: Sharing user
  Background:
    Given user "test1" exists
    Given as user "test1"
    Given user "test2" exists
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

  Scenario: Downloading file is blocked
    Given User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    And The webdav response should have a status code "201"
    And user "test1" shares file "/foobar.txt" with user "test2"
    And as user "test2"
    When Downloading file "/foobar.txt"
    Then The webdav response should have a status code "200"
    When Downloading file "/foobar.txt" with range "1-4"
    Then The webdav response should have a status code "200"
    And user "admin" creates global flow with 200
      | name      | Admin flow                       |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | deny                             |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    And as user "test2"
    When Downloading file "/foobar.txt"
    Then The webdav response should have a status code "403"
    When Downloading file "/foobar.txt" with range "1-4"
    Then The webdav response should have a status code "403"

  Scenario: Updating file is blocked
    Given User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    And The webdav response should have a status code "201"
    And user "test1" shares file "/foobar.txt" with user "test2"
    And user "admin" creates global flow with 200
      | name      | Admin flow                       |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | deny                             |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    And as user "test2"
    When User "test2" uploads file "data/textfile-2.txt" to "/foobar.txt"
    Then The webdav response should have a status code "403"

  Scenario: Deleting file that is shared directly is not blocked because it unshares from self
    Given User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    And The webdav response should have a status code "201"
    And user "test1" shares file "/foobar.txt" with user "test2"
    And user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "foobar.txt"} |
    And as user "test2"
    When User "test2" deletes file "/foobar.txt"
    Then The webdav response should have a status code "204"

  Scenario: Deleting file inside a shared folder is blocked
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
    And as user "test2"
    When User "test2" deletes file "/subdir/foobar.txt"
    Then The webdav response should have a status code "403"

  Scenario: Upload and share a file that is allowed by mimetype excludes
    And user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "!is", "value": "httpd/directory"} |
    | checks-1  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "!is", "value": "application/pdf"} |

    Given User "test1" uploads file "data/nextcloud.pdf" to "/nextcloud.pdf"
    And The webdav response should have a status code "201"
    And user "test1" shares file "/nextcloud.pdf" with user "test2"
    And Downloading file "/nextcloud.pdf" as "test1"
    And The webdav response should have a status code "200"
    And Downloading file "/nextcloud.pdf" as "test2"
    And The webdav response should have a status code "200"

  Scenario: Share a file that is allowed by mimetype excludes
    Given User "test1" uploads file "data/nextcloud.pdf" to "/nextcloud2.pdf"
    And The webdav response should have a status code "201"
    And user "test1" shares file "/nextcloud2.pdf" with user "test2"
    And Downloading file "/nextcloud2.pdf" as "test1"
    And The webdav response should have a status code "200"
    And user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "!is", "value": "httpd/directory"} |
    | checks-1  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "!is", "value": "application/pdf"} |
    And Downloading file "/nextcloud2.pdf" as "test2"
    And The webdav response should have a status code "200"

  Scenario: Jailed storage cache bug blocking first
    Given Ensure tag exists
    Given User "test1" uploads file "data/textfile.txt" to "/nextcloud2.txt"
    And The webdav response should have a status code "201"
    Given User "test1" uploads file "data/textfile.txt" to "/nextcloud3.txt"
    And The webdav response should have a status code "201"
    And user "test1" shares file "/nextcloud2.txt" with user "test2"
    And Downloading file "/nextcloud2.txt" as "test2"
    And The webdav response should have a status code "200"
    And user "test1" shares file "/nextcloud3.txt" with user "test2"
    And Downloading file "/nextcloud3.txt" as "test2"
    And The webdav response should have a status code "200"
    And user "test1" tags file "/nextcloud2.txt"
    When user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileSystemTags", "operator": "is", "value": "{{{FILES_ACCESSCONTROL_INTEGRATIONTEST_TAGID}}}"} |
    Then Downloading file "/nextcloud2.txt" as "test2"
    And The webdav response should have a status code "403"
    And Downloading file "/nextcloud3.txt" as "test2"
    And The webdav response should have a status code "200"
    And user "test2" should see following elements
      | /nextcloud3.txt |

  Scenario: Jailed storage cache bug blocking last
    Given Ensure tag exists
    Given User "test1" uploads file "data/textfile.txt" to "/nextcloud2.txt"
    And The webdav response should have a status code "201"
    Given User "test1" uploads file "data/textfile.txt" to "/nextcloud3.txt"
    And The webdav response should have a status code "201"
    And user "test1" shares file "/nextcloud2.txt" with user "test2"
    And Downloading file "/nextcloud2.txt" as "test2"
    And The webdav response should have a status code "200"
    And user "test1" shares file "/nextcloud3.txt" with user "test2"
    And Downloading file "/nextcloud3.txt" as "test2"
    And The webdav response should have a status code "200"
    And user "test1" tags file "/nextcloud3.txt"
    When user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileSystemTags", "operator": "is", "value": "{{{FILES_ACCESSCONTROL_INTEGRATIONTEST_TAGID}}}"} |
    Then Downloading file "/nextcloud2.txt" as "test2"
    And The webdav response should have a status code "200"
    And Downloading file "/nextcloud3.txt" as "test2"
    And The webdav response should have a status code "403"
    And user "test2" should see following elements
      | /nextcloud2.txt |

  Scenario: Downloading is still blocked when Secure View is enabled
    Given the following files app config is set
      | watermark_enabled | yes |
    Given User "test1" uploads file "data/textfile.txt" to "/foobar.txt"
    And The webdav response should have a status code "201"
    And user "test1" shares file "/foobar.txt" with user "test2"
    And as user "test2"
    When File "/foobar.txt" should have prop "oc:permissions" equal to "SRGDNVW"
    When Downloading file "/foobar.txt"
    Then The webdav response should have a status code "200"
    When Downloading file "/foobar.txt" with range "1-4"
    Then The webdav response should have a status code "200"
    And user "test1" shares file "/foobar.txt" publicly
    And as user "test2"
    When Downloading last public shared file with range "1-4"
    Then The webdav response should have a status code "200"
    And user "admin" creates global flow with 200
      | name      | Admin flow                       |
      | class     | OCA\FilesAccessControl\Operation |
      | entity    | OCA\WorkflowEngine\Entity\File   |
      | events    | []                               |
      | operation | deny                             |
      | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileMimeType", "operator": "is", "value": "text/plain"} |
    And as user "test2"
    When Propfind for file "/foobar.txt" prop "oc:permissions" fails with 403 "<s:message>No read permissions."
    When Downloading file "/foobar.txt"
    Then The webdav response should have a status code "403"
    When Downloading file "/foobar.txt" with range "1-4"
    Then The webdav response should have a status code "403"
    When Downloading last public shared file with range "1-4"
    Then The webdav response should have a status code "404"
