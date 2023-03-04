Changelog
=========


2.1.0 (2023-03-04)
------------------

Features
~~~~~~~~
- Overwritable login provider templates #12. [Ralf Zimmermann]

Other
~~~~~
- Merge tag '2.0.2' into develop. [Ralf Zimmermann]

  2.0.2


2.0.2 (2023-03-04)
------------------

Tasks
~~~~~
- Update changelog. [Ralf Zimmermann]
- Version 2.0.2. [Ralf Zimmermann]
- Fix license. [Ralf Zimmermann]

Bugfixes
~~~~~~~~
- Load dependencies in non composer mode #6 #14 (#16) [Ralf Zimmermann,
  waldhacker-joerg]

  * [BUGFIX] The missing classes are now included via the ext_emconf.php
  autoloader configuration. #6  #14

  * [TASK] TYPO3 v10 non composer compatibility

  ---------

Other
~~~~~
- Merge branch 'release/2.0.2' into main. [Ralf Zimmermann]
- Merge tag '2.0.1' into develop. [Ralf Zimmermann]

  2.0.1


2.0.1 (2022-09-20)
------------------

Tasks
~~~~~
- Prepare release 2.0.1. [Ralf Zimmermann]

Bugfixes
~~~~~~~~
- Fix missing fe_login flexform data within functional tests. [Ralf
  Zimmermann]
- Fix query restriction errors in cli mode #8. [Ralf Zimmermann]
- PHP 8 compatibility in authentication services #10. [Ralf Zimmermann]

Other
~~~~~
- Merge branch 'release/2.0.1' into main. [Ralf Zimmermann]
- Merge tag '2.0.0' into develop. [waldhacker1]

  2.0.0


2.0.0 (2022-04-02)
------------------

Tasks
~~~~~
- Prepare release 2.0.0. [waldhacker1]

Features
~~~~~~~~
- Frontend integration (#5) [Ralf Zimmermann]

  * [FEATURE] Frontend integration

  * [TASK] TYPO3 v10 compat

  * [FEATURE] Add redirect from query

  * [DOCS] Add frontend integration docs

Other
~~~~~
- Merge branch 'release/2.0.0' into main. [waldhacker1]
- [DOCS] Add more frontend integration docs. [waldhacker1]
- Merge tag '1.0.1' into develop. [waldhacker1]

  1.0.1


1.0.1 (2022-03-11)
------------------

Tasks
~~~~~
- Prepare release 1.0.1. [waldhacker1]
- Change phpstan baseline. [waldhacker1]
- Remove deprecated psalm config. [Ralf Zimmermann]
- Check if SESSION exists. [Fabian Auer]

Bugfixes
~~~~~~~~
- PasteUpdate not always string. move ce in backend. [Fabian Auer]

Other
~~~~~
- Merge branch 'release/1.0.1' into main. [waldhacker1]
- Merge pull request #4 from huersch/main. [Ralf Zimmermann]

  [BUGFIX] Avoid PHP warnings and enable moving CEs in backend
- Merge pull request #2 from huersch/patch-1. [Ralf Zimmermann]

  typo in event FQCN. remove leading backslash
- Typo in event FQCN. remove leading backslash. [Fabian Auer]


1.0.0 (2021-07-11)
------------------

Tasks
~~~~~
- Fix ext_emconf. [Susanne Moog]
- Prepare Release. [Susanne Moog]
- Add TER release capabilities. [Susanne Moog]
- Add Readme. [Susanne Moog]
- Add Readme. [Susanne Moog]
- Fix ci errors. [Ralf Zimmermann]
- Fix ci errors. [Ralf Zimmermann]
- Use correct host. [Susanne Moog]
- Use correct image name. [Susanne Moog]
- Try docker runner. [Susanne Moog]
- Add runner tags. [Susanne Moog]
- Remove superfluous typoscript linting. [Susanne Moog]
- Fix tag. [Susanne Moog]
- Switch base image. [Susanne Moog]
- Add platform. [Susanne Moog]
- Switch composer image. [Susanne Moog]
- Add .gitlab internal pipeline. [Susanne Moog]
- Update changelog paths. [Susanne Moog]
- Add Changelog. [Susanne Moog]

Features
~~~~~~~~
- Add local dev env. [Susanne Moog]
- Add Documentation. [Susanne Moog]
- Initial version. [Susanne Moog]

Bugfixes
~~~~~~~~
- Harden config handling. [Susanne Moog]

Other
~~~~~
- Merge branch 'security/restrict-config-edit' into 'main' [Susanne
  Moog]

  [SECURITY] Restrict read/write access to tx_oauth2_client_configs

  See merge request waldhacker/typo3/oauth2-client!1
- [SECURITY] Restrict read/write access to tx_oauth2_client_configs.
  [Ralf Zimmermann]


