#!/usr/bin/env bash

#
# script is not intended to be executed directly. use `pnpm exec ...` instead or call it as package script.
#
# the workflow in detail:
# - check if a release with flag "pre-release" exists
# - check if a 'latest' release exists
#   - if not, create a 'latest' release
# - take over commit hash, and assets from the 'pre-release' to the 'latest' release
#   - semantic versions in assets will be renamed to 'latest'
#       (example: ionos-essentials-0.1.1-php7.4.zip => ionos-essentials-latest-php7.4.zip)
#   - release note will be set to the 'pre-release' release url and title to make it easier to find the origin release
# - remove the 'pre-release' flag from the release used to populate the 'latest' release
#
# afterwards the 'latest' release will contain the same assets as the 'pre-release' release
# except that semantic version numbers in asstes filenames are replaced with 'latest'
#

# bootstrap the environment
source "$(realpath $0 | xargs dirname)/includes/bootstrap.sh"

# ensure we have a GITHUB_TOKEN
if [[ -z "${GITHUB_TOKEN}" ]]; then
  ionos.wordpress.log_error "GITHUB_TOKEN environment variable is not set."
  exit 1
fi

# set GH_TOKEN to GITHUB_TOKEN if not set
# this is needed for gh cli to work
export GH_TOKEN=${GH_TOKEN:-$GITHUB_TOKEN}

readonly LATEST_RELEASE_TAG="@ionos-wordpress/latest"

# get pre-release flagged release
PRE_RELEASE=$(gh release list --json name,isPrerelease | jq -r '.[] | select(.isPrerelease == true) | .name')
if [[ $(echo "$PRE_RELEASE" | wc -l) -ne 1 ]]; then
  error_message="skip releasing - expected exactly one release flagged as 'pre-release' but found $(echo "$PRE_RELEASE" | wc -l)"
  [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
  ionos.wordpress.log_error "$error_message\n$PRE_RELEASE"
  exit 1
else
  PRE_RELEASE=$(echo "$PRE_RELEASE" | head -n 1)
  ionos.wordpress.log_header "Releasing $PRE_RELEASE"
fi

# get or create the release titled 'latest'
readonly LATEST_RELEASE=$(gh release list --json tagName,isLatest | jq -r '.[] | select(.isLatest == true) | .tagName')
if [[ "$LATEST_RELEASE" != "$LATEST_RELEASE_TAG" ]]; then
  ionos.wordpress.log_info "did not found a release named/tagged '$LATEST_RELEASE_TAG'"

  # ensure there is no tag named "$LATEST_RELEASE_TAG"
  git tag -d "$LATEST_RELEASE_TAG" 2>/dev/null ||:
  git push origin --delete "$LATEST_RELEASE_TAG" 2>/dev/null ||:

  # create release
  gh release create "$LATEST_RELEASE_TAG" --notes '' --title="$LATEST_RELEASE_TAG" --latest=true
  # 2>/dev/null
  echo "created release '$LATEST_RELEASE_TAG'"
fi

# Get the commit hash of the tag associated with the pre-release
readonly PRE_RELEASE_COMMIT_HASH=$(git rev-list -n 1 "$PRE_RELEASE")

# update 'latest' release data
readonly PRE_RELEASE_URL="https://github.com/lgersman/ionos-wordpress/releases/tag/$(printf $PRE_RELEASE | jq -Rrs '@uri')"
gh release edit "$LATEST_RELEASE_TAG" \
  --title "$LATEST_RELEASE_TAG" \
  --target $PRE_RELEASE_COMMIT_HASH \
  --notes "latest release is release [$PRE_RELEASE]($PRE_RELEASE_URL)" \
  --tag $LATEST_RELEASE_TAG \
  --latest \
  1>/dev/null

# update latest release assets
ASSETS=$(gh release view $PRE_RELEASE --json assets --jq '.assets[] | .name')
for ASSET in $ASSETS; do
  TARGET_ASSET_FILENAME=$(echo $ASSET | sed -E 's/[0-9]+\.[0-9]+\.[0-9]+/latest/g')
  rm -f $TARGET_ASSET_FILENAME
  echo "upload release '$PRE_RELEASE' asset '$ASSET' as '$TARGET_ASSET_FILENAME' to release '$LATEST_RELEASE_TAG'"
  gh release download $PRE_RELEASE --pattern $ASSET -O $TARGET_ASSET_FILENAME
  if ! gh release upload $LATEST_RELEASE $TARGET_ASSET_FILENAME --clobber; then
    $error_message="Failed to upload asset $TARGET_ASSET_FILENAME"
    [[ "${CI:-}" == "true" ]] && echo "::error:: $error_message"
    echo "$error_message"
  fi
  rm -f $TARGET_ASSET_FILENAME
done

readonly success_message="Successfully updated release '$LATEST_RELEASE_TAG' to point to release ${LATEST_RELEASE}(commit $PRE_RELEASE_COMMIT_HASH)"
[[ "${CI:-}" == "true" ]] && echo "::summary:: $success_message"
echo "$success_message"


