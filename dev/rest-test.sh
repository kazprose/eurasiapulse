#!/usr/bin/env bash
# REST round-trip test: create a post with regions / formats / meta via the API, read it back.
# Usage: bash dev/rest-test.sh   (local server on :8080, theme active)
set -u
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BASE="http://127.0.0.1:8080/wp-json/wp/v2"
PASS="$("$ROOT/.local/php/php.exe" "$ROOT/dev/app-password.php")"
AUTH="admin:$PASS"
fail=0
check() { if [ "$1" = "$2" ]; then echo "PASS  $3"; else echo "FAIL  $3 (got: $1, expected: $2)"; fail=1; fi; }

echo "== Taxonomy endpoints (public) =="
REGION_ID=$(curl -s "$BASE/regions?slug=kazakhstan" | node -pe 'JSON.parse(require("fs").readFileSync(0,"utf8"))[0].id')
FORMAT_ID=$(curl -s "$BASE/formats?slug=analysis" | node -pe 'JSON.parse(require("fs").readFileSync(0,"utf8"))[0].id')
CAT_ID=$(curl -s "$BASE/categories?slug=economy" | node -pe 'JSON.parse(require("fs").readFileSync(0,"utf8"))[0].id')
echo "regions/kazakhstan=$REGION_ID formats/analysis=$FORMAT_ID categories/economy=$CAT_ID"
check "$([ -n "$REGION_ID" ] && echo ok)" "ok" "GET /wp/v2/regions returns the region term"
check "$([ -n "$FORMAT_ID" ] && echo ok)" "ok" "GET /wp/v2/formats returns the format term"

echo "== Create post via REST (authenticated) =="
cat > "$ROOT/.local/rest-post.json" <<JSON
{
  "title": "REST round-trip test post",
  "content": "<!-- wp:paragraph --><p>Body created through the REST API.</p><!-- /wp:paragraph -->",
  "status": "publish",
  "categories": [$CAT_ID],
  "regions": [$REGION_ID],
  "formats": [$FORMAT_ID],
  "meta": {
    "ep_subtitle": "Subtitle written through the REST API",
    "ep_source_name": "Sample source",
    "ep_source_url": "https://example.com/rest-source",
    "ep_image_credit": "Photo: Placeholder agency"
  }
}
JSON
CREATE=$(curl -s -u "$AUTH" -H "Content-Type: application/json" -d @"$ROOT/.local/rest-post.json" "$BASE/posts")
POST_ID=$(echo "$CREATE" | node -pe 'const j=JSON.parse(require("fs").readFileSync(0,"utf8")); j.id||("ERROR: "+JSON.stringify(j))')
echo "created post id: $POST_ID"

echo "== Read back (public, no auth) =="
READ=$(curl -s "$BASE/posts/$POST_ID")
J() { echo "$READ" | node -pe "const j=JSON.parse(require('fs').readFileSync(0,'utf8')); $1"; }
check "$(J 'j.regions.join(",")')" "$REGION_ID" "regions field readable"
check "$(J 'j.formats.join(",")')" "$FORMAT_ID" "formats field readable"
check "$(J 'j.meta.ep_subtitle')" "Subtitle written through the REST API" "meta.ep_subtitle readable"
check "$(J 'j.meta.ep_source_name')" "Sample source" "meta.ep_source_name readable"
check "$(J 'j.meta.ep_source_url')" "https://example.com/rest-source" "meta.ep_source_url readable"
check "$(J 'j.meta.ep_image_credit')" "Photo: Placeholder agency" "meta.ep_image_credit readable"

echo "== Update via REST: change region/format, reject a javascript: URL =="
UPDATE=$(curl -s -u "$AUTH" -H "Content-Type: application/json" -X POST -d "{\"regions\":[],\"formats\":[],\"meta\":{\"ep_source_url\":\"javascript:alert(1)\",\"ep_subtitle\":\"<b>bold</b> stripped\"}}" "$BASE/posts/$POST_ID")
READ=$(curl -s "$BASE/posts/$POST_ID")
check "$(J 'j.regions.length')" "0" "regions writable (cleared)"
check "$(J 'j.formats.length')" "0" "formats writable (cleared)"
check "$(J 'JSON.stringify(j.meta.ep_source_url)')" '""' "javascript: URL sanitized to empty"
check "$(J 'j.meta.ep_subtitle')" "bold stripped" "HTML stripped from subtitle"

echo "== Unauthenticated write must be rejected =="
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "Content-Type: application/json" -X POST -d '{"meta":{"ep_subtitle":"x"}}' "$BASE/posts/$POST_ID")
check "$CODE" "401" "anonymous POST rejected with 401"

echo "== Front-end render of the REST-created post =="
LINK=$(J 'j.link')
HTML=$(curl -s "$LINK")
check "$(echo "$HTML" | grep -c 'article__dek">bold stripped')" "1" "updated subtitle rendered on the article page"

echo "== Cleanup =="
curl -s -u "$AUTH" -X DELETE "$BASE/posts/$POST_ID?force=true" -o /dev/null
echo "deleted test post $POST_ID"
[ "$fail" = 0 ] && echo "ALL REST CHECKS PASSED" || echo "SOME REST CHECKS FAILED"
exit $fail
