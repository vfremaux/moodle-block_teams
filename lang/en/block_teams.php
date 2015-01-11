<?php

// Capabilities
$string['teams:addinstance'] = 'Can add an instance';
$string['teams:myaddinstance'] = 'Can add an instance to my page';
$string['teams:creategroup'] = 'Can create a team';
$string['teams:transferownership'] = 'Can transfer ownership';
$string['teams:apply'] = 'Can apply to an open team';

//groups stuff

$string['accept'] = 'Accept';
$string['acceptinvite'] = 'Are you sure you want to accept this invitation';
$string['allowleaderteams'] = 'Allow user to lead several teams';
$string['allowleadmultipleteams'] = 'Allow user to lead several teams';
$string['allowmultipleteams'] = 'Allow user to belong to several teams';
$string['allowteamsiteinvite'] = 'Invite members from whole site';
$string['alreadyinagroup'] = 'You are already in a team inside this course';
$string['alreadyinvited'] = 'This user has already been invited to join one team';
$string['alreadyinvitedtogroup'] = 'This user has already been invited to join the team';
$string['allowrequests'] = 'Allow join requests to open groups';
$string['backtocourse'] = 'Back to course';
$string['blockname'] = 'Teams';
$string['configdefaultteamvisibility'] = 'If checked, all teams are visible for everyone. if unchecked, users will only see teams they are member of or they are invited in';
$string['configdefaultteaminviteneedsacceptance'] = 'If enabled, an invite needs to be acknowledged by the invited user. If disabled, an invited user is directly set as active member of the group. this is the default value for any new Teams block.';
$string['configteamleaderrole'] = 'If set, any group leader will get an additional role assignation to this role';
$string['configteammaxsizedefault'] = 'Set a default limit size for a team (applicable to all site). 0 stands for no limit.';
$string['configteamsiteinvite'] = 'If enabled, teachers will be able to open the invitation range to all site. In this case, new team members will also be enrolled in the course';
$string['confirmacceptemailsubject'] = 'Course Team Invite Accepted';
$string['confirmdeclineemailsubject'] = 'Course Team Invite Declined';
$string['createnewgroup'] = 'Create New Team';
$string['createnewgroupdesc'] = 'By creating a team, you will become the leader and will be able to invite others to join.';
$string['decline'] = 'Decline';
$string['declineinvite'] = 'Are you sure you want to decline this invitation';
$string['deleteconfirmemailsubject'] = 'course Team Membership Removed';
$string['deletegroup'] = 'Delete this team';
$string['deleteinvconfirmemailsubject'] = 'course Team Invite Removed';
$string['deletemember'] = 'Revoke this member';
$string['emailconfirmsent'] = 'An invitation email should have been sent to the address at <b>{$a}</b>';
$string['errorbaduser'] = 'current user is not in invite';
$string['errorcouldnotassignmember'] = 'could not insert record into groups members table';
$string['errordeleteleader'] = 'You are leader of this group. You cannot delete yourself from the team until you give leadership to someone else !';
$string['erroremptygroupname'] = 'Group name cannot be empty';
$string['errorgroupdelete'] = 'you cannot delete this group';
$string['errorinvalidaction'] = 'Invalid Action';
$string['errorinvalidgroupid'] = 'Invalid group id';
$string['errorinvalidinvite'] = 'Ivalid Invitation';
$string['errorinvalidrequest'] = 'Invalid Request';
$string['errornoleader'] = 'you are not the leader of this group';
$string['errornomember'] = 'you are not a member of this group';
$string['errorregisterteam'] = 'Could not register the team';
$string['groupcreated'] = 'Team created successfully';
$string['groupdeleted'] = 'Team deleted sucessfully';
$string['groupexists'] = 'A team already exists with that name, please try again.';
$string['groupfull'] = 'The maximum number of team members has been reached and you cannot invite any more users';
$string['groupinvites'] = 'Team Invites';
$string['groupinvitesdesc'] = 'You have been invited to join the following teams';
$string['groupmax'] = 'Max Team size';
$string['groupmaxdesc'] = 'This is the maximum number of users that may be assigned to a course team. It does not alter the members of an existing team if the number exceeds the one set here, but will prevent more users from being added.';
$string['groupmembers'] = 'Current Team Members';
$string['groupmessagesent'] = 'Team Messages Sent';
$string['groupmodenotset'] = 'This block requires the course Group mode to be "separate" or "visible" at least';
$string['groupname'] = 'Team name';
$string['grouprequests'] ='Membership requests';
$string['grouprequestsdesc'] ='You have join requests pending';
$string['invalidinvite'] = 'invalid invite';
$string['inviteaccepted'] = 'You have been added to this team';
$string['inviteauser'] = 'Invite a user';
$string['invited'] = 'Invited';
$string['invitedeclined'] = 'You have declined this invitation';
$string['invitedeleted'] = 'Team invite deleted sucessfully';
$string['inviteemailsubject'] = 'Team Invite';
$string['invitegroupmembers'] = 'Invite team members';
$string['invitesent'] = 'This user has been invited to join the team';
$string['invitethisuser'] = 'Invite user';
$string['joinrequestposted'] = 'A requet to join has been sent to leaders.';
$string['pendingrequest'] = '<i>Pending join request...</i>';
$string['jointeam'] = 'Join Team';
$string['leader'] = 'Leader';
$string['localsettings'] = 'Local Settings';
$string['lpmustbepublished'] = 'This course must be published to view teams';
$string['manageteamgroup'] = 'Manage Team';
$string['memberadded'] = 'Member has been added';
$string['memberdeleted'] = 'User has been deleted from the team';
$string['message'] = 'Message';
$string['messagebody'] = 'Message body';
$string['messagegroup'] = 'Send a message to all team members';
$string['messagenorecipients'] = 'Sorry, but there were no users to message matching the selected list';
$string['missingidnumber'] = 'Missing ID number';
$string['nogroupset'] = 'You are not yet a member of a team on this course';
$string['nogroupsetwarning'] = 'You must be a member of a team in this course';
$string['noinvites'] = 'You have not been invited to join any existing teams';
$string['norequests'] = 'You have no join requests pending in your leaded teams';
$string['nouserfound'] = 'Could not find any user with those details';
$string['pluginname'] = 'Teams';
$string['reject'] = 'Reject';
$string['rejectconfirm'] = 'Are you sure you want to reject the join request of {$a->name} in team {$a->group}?';
$string['removefromgroup'] = 'Are you sure you want to delete the user: {$a->name} from the team: {$a->group}?';
$string['removegroup'] = 'Are you sure you want to delete the team: {$a->group} - this will remove all existing members, and delete all team calendar events.';
$string['removemefromgroup'] ='Remove me from this team';
$string['revokeinvite'] = 'Delete this invite';
$string['requestrejected'] = 'Join request rejected';
$string['requestaccepted'] = 'Join request accepted';
$string['searchforusers'] = 'Search for users';
$string['searchforusersdesc'] = 'To find a user, you must add a filter using the form below. For more filter options press the "Show Advanced" button.';
$string['selecttransferuser'] = 'Select an existing team member';
$string['sendingmessagetoteam'] = 'Sending message to team';
$string['sendmessage'] = 'Send';
$string['similarusers'] = 'Users with similar interests';
$string['startmygroup'] = 'Start My Own Team';
$string['team'] = 'Team';
$string['teamgroup'] = 'Team: {$a}';
$string['teamgroups'] = 'Teams';
$string['teaminviteneedsacceptance'] = 'Enable invite acceptance';
$string['defaultteaminviteneedsacceptance'] = 'Enable invite acceptance (default)';
$string['defaultteamvisibility'] = 'Default team visibility';
$string['teamvisibility'] = 'Default team visibility';
$string['teamleaderrole'] = 'Leader additional role';
$string['teammaxsizedefault'] = 'Team max size default';
$string['teamsiteinvite'] = 'Allow site level invitation';
$string['teamsmaxsize'] = 'Team max size (leader included)';
$string['transferconfirmed'] = 'Leadership has been Transferred Successfully';
$string['transferleadership'] = 'Transfer Leadership';
$string['transferto'] = 'Transfer Leadership to';
$string['transferuser'] = 'Are you sure you want to transfer leadership for the team: {$a->group}, to the user: {$a->user}';
$string['useralreadyingroup'] = 'That user is already assigned to a team in this course';
$string['openteam'] = 'Make team visible to all';
$string['closeteam'] = 'Make team private';
$string['open'] = 'Team is visible';
$string['closed'] = 'Team is private';
$string['forceinvite'] = 'Force invite';
$string['inviteforced'] = 'Invite has been forced in.';
$string['jointeam'] = 'Join this team';

$string['initiallyclosed'] = 'Initially private';
$string['initiallyopen'] = 'Initially visible';
$string['forcedclosed'] = 'Private (forced)';
$string['forcedopen'] = 'Visible (forced)';

/* messages */

$string['inviteemailsubject'] = 'Teams / Group invite';
$string['inviteemailbody'] = 'Hi, {$a->firstname},

I invite you to join the team "{$a->group}" in the course "{$a->course}", use the following link for more information
{$a->link}
';

$string['addmembermailsubject'] = 'Teams / Group registered';
$string['addmemberemailbody'] = 'Hi, {$a->firstname},

I have added you to my team "{$a->group}" in the course "{$a->course}", use the following link for more information
{$a->link}
';

$string['confirmdeclineemailsubject'] = 'Teams / Group invite declined';
$string['confirmdeclineemailbody'] = 'Hi {$a->firstname},

{$a->user} has declined your invitation to join the team "{$a->group}" in the course "{$a->course}"
';

$string['confirmacceptemailsubject'] = 'Teams / Group invite accepted';
$string['confirmacceptemailbody'] = 'Hi {$a->firstname},

{$a->user} has accepted your invitation to join the Team "{$a->group}" in the course "{$a->course}"';

$string['deleteconfirmemailsubject'] = 'Teams / Member deletion';
$string['deleteconfirmemailbody'] = 'Hi {$a->firstname},

{$a->user} has been removed from the Team "{$a->group}" in the course "{$a->course}"
';

$string['deleteinvconfirmemailsubject'] = 'Teams / Invite deletion';
$string['deleteinvconfirmemailbody'] = 'Hi {$a->firstname},

The invite for {$a->user} to the Team "{$a->group}" in the course "{$a->course}" has been removed.
';

$string['rejectconfirmemailsubject'] = 'Teams / Join request rejected.';
$string['rejectconfirmemailbody'] = 'Hi {$a->firstname},

Your request to join group "{$a->group}" in course "{$a->course}" has beed rejected.
';

$string['acceptjoinemailsubject'] = 'Demande d\'accès au groupe acceptée.';
$string['acceptjoinemailbody'] = 'Bonjour {$a->firstname},

Your request to join group "{$a->group}" in course <a href="{$a->courseurl}">"{$a->course}"</a> has been accepted. You are now member of the group.
';

$string['emailconfirmation'] = 'Hi {$a->firstname},

{$a->fromuser} has invited you to create an account at "{$a->sitename}"

{$a->custommsg}

To confirm your new account, please go to this web address:

{$a->link}

In most mail programs, this should appear as a blue link
which you can just click on.  If that doesn\'t work,
then cut and paste the address into the address
line at the top of your web browser window.

If you need help, please contact the site administrator,
{$a->admin}
';

/* Helpers */

$string['groupinvites_help'] = '
This section lists people having received an invitation to enter the team from the team leader. Invited users should accept the invitation before
they are actually member of the group.
';

$string['teamvisibility_help'] = '
Teams can be private or visible. this setting determines the initial state of any new teams, and wether this state can be changed or not:

* Initially private: New teams are private, but leaders can publish them.
* Initially visible: New teams are visible, but leaders might turn them private.
* Forced private: All teams remain private. If this option is turned on after teams have been created, all older teams will have state changed to private.
* Forced visible: All teams remain visible. If this option is turned on after teams have been created, all older teams will have state changed to visible.
';

$string['teaminviteneedsacceptance_help'] = '
If invite acceptance is enabled (default), then an invited user will have to explictely acknowledge the invite before he actually gets group membership.

If this feature is disabled, group leaders can add members directly whthout having to wait for peer confirmation.
';