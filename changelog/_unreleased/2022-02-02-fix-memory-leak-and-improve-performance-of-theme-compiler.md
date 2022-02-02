---
title: Improve performance and fix memory leak of theme compiler
author: Lech Groblewicz
author_github: @xrogers
---
# Storefront
* Initialize SCSS compiler on every compilation, because it is leaking memory and stack during compilation. Because of that in the shops with many sales channels it slows down gradually and fails at some point with error about infinite loop.
* Add option to use SCSS cache to further improve performance of the theme compilation.

