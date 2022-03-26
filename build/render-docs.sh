#!/bin/bash

source <(docker run --rm t3docs/render-documentation show-shell-commands)
dockrun_t3rd makehtml
xdg-open "Documentation-GENERATED-temp/Result/project/0.0.0/Index.html"
