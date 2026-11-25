VERDICT: PASS

Round 2 compat: no breaking changes. PrefixSearch is a new internal Support class; Action signatures unchanged. User list/show events gained the ReadActionEvent marker (additive). No unmarked User Listed/Shown events remain, so the listener narrowing changes no behaviour.
