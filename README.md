# Case Tools

Case Tools adds some behind-the-scenes improvements to CiviCase.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.6+
* CiviCRM 5.7+

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl at.greenpeace.casetools@https://github.com/greenpeace-cee/at.greenpeace.casetools/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/greenpeace-cee/at.greenpeace.casetools.git
cv en casetools
```

## Core API Improvements

#### The `Case.create` API was changed to accept parameters:

##### Params related to case managers:
- `new_case_manager_ids`(array) - updates case managers. All case managers not in this param will be removed.
- `track_managers_change`(bool) - if it checked, it creates new activity(type=`Reassigned Case`) where in details you can see which case managers was before and after.
- `managers_change_activity_params`(array) - allows to add extra params to activity which will created when `track_managers_change` param is checked. List of available params you can see in `Case.create` API. 
---
##### Params related to case status:
- `track_status_change`(bool) - if it checked, it creates new activity(type=`Change Case Status`) where in subject you can see which status was before and after.
- `managers_change_activity_params`(array) - allows to add extra params to activity which will created when `track_status_change` param is checked. List of available params you can see in `Case.create` API.
---
##### Params related to case tags:
- `tags_ids`(array) - updates case tags. All case tags not in this param will be removed.
- `is_only_add_tags`(bool) - if it checked, it only add new tags from `tags_ids` param and will not remove tags not in `tags_ids` param.
- `track_tags_change`(bool) - if it checked, it creates new activity(type=`Change Case Tags`) where in details you can see which tags was before and after.
- `tags_change_activity_params`(array) - allows to add extra params to activity which will created when `track_tags_change` param is checked. List of available params you can see in `Case.create` API.
---
### New APIs:

#### `CaseTools.get_case_managers`

This API returns list of case manager ids.

Available params:
- `case_id`(int)(required) - Case id. 

Results example:
```php
[
  'manager_ids' => [1, 2, 3]
]
```
---
#### `CaseTools.get_manager_settings`

This API returns case manager settings.

Available params:
- `case_id`(int)(required) - Case id. 

Results example:
```php
[
    'case_manager_relationship_settings' => [
        'case_type' => 'your_case_type_name',
        'manager_relationship_type_id' => '9',
        'manager_relationship_direction' => '_a_b',
        'manager_column_name' => "contact_id_b",
        'client_column_name' => "contact_id_a"
        'manager_relationship_type' => [
            'id': '9',
            'name_a_b' => "Case Coordinator is",
            'label_a_b' => "Case Coordinator is",
            'name_b_a' => "Case Coordinator",
            'label_b_a' => "Case Coordinator",
            'description' => "Case Coordinator",
            'contact_type_a' => "Individual",
            'contact_type_b' => "Individual",
            'is_reserved' => "0",
            'is_active' => "1"
        ],
    ]
]
```
---
#### `Activity.fileoncase`
The `Activity.fileoncase` API allows filing activities on cases.

## Known Issues
