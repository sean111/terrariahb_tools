Description
===========
This is the toolset I created to help generate the TerrariaHB code. I use ant so that I can chain commands together without writing too much crap to do it.

Version 1
=========

Ant Targets:
------------
wiki    - This will run the wiki script which will parse wiki data for items and monsters (only does certain blocks)
output  - This will run the output script which uses the template to generate the indexFile
build   - This will run the output script than optimize the png files
optpng  - Optimize png file
opthml  - Optimize the html/css/js useing htmlcompressor