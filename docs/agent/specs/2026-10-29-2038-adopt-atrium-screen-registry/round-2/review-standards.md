VERDICT: PASS

Round 2 changes are test-only (ScreenControlsTest, RosterPluginTest, ScreenAccessOnTest, OrganizationActivityRemovedTest). No standard is broken.

- nit: tests/Modes/Keen/OrganizationActivityRemovedTest.php now compares two counts of audit-trail markup, so it is weaker than criterion 11 intends. Prefer asserting that no audit-trail appears outside a registered section.
