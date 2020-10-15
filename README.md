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

### Status Change Activity

The `Case.create` API was changed to accept a `track_status_change` parameter.
When this parameter is set to `TRUE`, and an API request is sent that changes
the status of an existing case, a "Change Case Status" activity is created.

The API also accepts the optional `status_change_activity` array parameter.
When is parameter is set, it's passed to the `Activity.create` call used to
create the "Change Case Status" activity, allowing API clients to change
the activity as needed (for example by adding a `medium_id` parameter).

### File Activity on Case

The `Activity.fileoncase` API allows filing activities on cases.

## Known Issues
