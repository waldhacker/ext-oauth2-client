#!/bin/bash

# needs https://pypi.org/project/gitchangelog/
export GITCHANGELOG_CONFIG_FILENAME=./build/.gitchangelog.rc
gitchangelog > Documentation/Changelog.rst

export GITCHANGELOG_CONFIG_FILENAME=./build/.gitchangelog-md.rc
gitchangelog > CHANGELOG.md
