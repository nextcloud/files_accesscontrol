Feature: manage-workflows
  Background:
    Given user "test1" exists
    Given as user "test1"

  Scenario: Admin creates a global flow
    Given user "admin" creates global flow with 200
    | name      | Admin flow                       |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "a"} |

  Scenario: Users can not create a global flow
    Given user "test1" creates global flow with 403
    | name      | User flow                        |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "a"} |

  # https://github.com/nextcloud/security-advisories/security/advisories/GHSA-h3c9-cmh8-7qpj
  Scenario: Users can not create a user flow
    Given user "test1" creates user flow with 400
    | name      | User flow                        |
    | class     | OCA\FilesAccessControl\Operation |
    | entity    | OCA\WorkflowEngine\Entity\File   |
    | events    | []                               |
    | operation | deny                             |
    | checks-0  | {"class":"OCA\\\\WorkflowEngine\\\\Check\\\\FileName", "operator": "is", "value": "a"} |

