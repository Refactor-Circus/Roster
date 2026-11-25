VERDICT: PASS

Round 2 security review. PrefixSearch keeps the escaping unchanged: the escape char, %, and _ are escaped; the pattern is bound as a parameter; column names come from fixed lists or roster.users.columns and are wrapped by the grammar; the ESCAPE literal is a constant. The ReadActionEvent change only narrows cache invalidation to the same set of read events and exposes no data. No findings.
