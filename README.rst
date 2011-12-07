Description
===========
Curent Version: 2

This is the toolset I created to help generate the TerrariaHB code. 
As of version 2 I now use Phing instead of ant

Version 2 (tests)
=================

Phing Targets:
--------------
all (default)   - Runs output, docs and optpng
output          - Executed output.php
jsmin           - Unused (will create minimized versions of the js files)
docs            - Create DocBlox documentation
optpng          - Optimize the png image files in output.dir/img/
