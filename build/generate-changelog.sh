#!/bin/bash

# needs https://pypi.org/project/gitchangelog/
#export GITCHANGELOG_CONFIG_FILENAME=./build/.gitchangelog.rc
#gitchangelog > Documentation/Changelog.rst

#export GITCHANGELOG_CONFIG_FILENAME=./build/.gitchangelog-md.rc
#gitchangelog > CHANGELOG.md

docker run -v $(pwd):/tmp --env GITCHANGELOG_CONFIG_FILENAME=/tmp/build/.gitchangelog.rc envcli/gitchangelog bash -c 'cd /tmp && gitchangelog' > Documentation/Changelog.rst
docker run -v $(pwd):/tmp --env GITCHANGELOG_CONFIG_FILENAME=/tmp/build/.gitchangelog-md.rc envcli/gitchangelog bash -c 'cd /tmp && gitchangelog' > CHANGELOG.md
