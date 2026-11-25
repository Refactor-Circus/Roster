// src/components/organizations/organizations.ts
var e = ["active", "inactive"], t = ["active", "inactive"], n = ["active", "pending"], r = [
	"members",
	"teams",
	"invitations",
	"settings"
];
function i(e) {
	return r.includes(e ?? "") ? e : "members";
}
function a(e, t) {
	let n = `/roster/organizations/${encodeURIComponent(e)}`;
	return t && t !== "members" ? `${n}?tab=${t}` : n;
}
function o(e, t) {
	return `/roster/organizations/${encodeURIComponent(e)}/teams/${encodeURIComponent(t)}`;
}
function s(e) {
	return e.split(/[\n,]/).map((e) => e.trim()).filter((e) => e !== "");
}
function c(e) {
	return e && (e.name || e.email) || null;
}
function l(e) {
	switch (e) {
		case "active":
		case "accepted": return "success";
		case "pending": return "info";
		case "expired":
		case "inactive": return "warning";
		case "declined":
		case "revoked": return "error";
		default: return "neutral";
	}
}
export { s as a, l as c, t as i, c as l, r as n, a as o, n as r, i as s, e as t, o as u };
