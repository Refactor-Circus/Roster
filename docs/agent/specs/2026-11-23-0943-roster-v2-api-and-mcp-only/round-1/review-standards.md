VERDICT: CHANGES

- minor: SearchUsersAction and SearchOrganizationsAction duplicate the LIKE escape logic (ESCAPE, startsWith(), escape()). Fix: move to one shared Support helper.
- minor: the RosterServiceProvider status-count listener keeps redundant ListedActionEvent/ShownActionEvent suffix checks next to the new ReadActionEvent check. Fix: rely on ReadActionEvent only if every read event implements it.
