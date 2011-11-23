Description
===========
This is the toolset I created to help generate the TerrariaHB code. I use ant so that I can chain commands together without writing too much crap to do it.

Due to running both versions of the parser at the same time I have version 2 in the tests folder (also too lazy to branch and re-clone)

Version 1 (base)
================

Ant Targets:
------------
wiki    - This will run the wiki script which will parse wiki data for items and monsters (only does certain blocks)
output  - This will run the output script which uses the template to generate the indexFile
build   - This will run the output script than optimize the png files
optpng  - Optimize png file
opthml  - Optimize the html/css/js useing htmlcompressor

Version 2 (tests)
=================

Phing Targets:
--------------
all (default)   - Runs output, docs and optpng
output          - Executed output.php
jsmin           - Unused (will create minimized versions of the js files)
docs            - Create DocBlox documentation
optpng          - Optimize the png image files in output.dir/img/
