#!/bin/bash

git init
git add .
git commit -m "Initial commit of api-validation"
#create origin first time through
#git remote add origin https://github.com/PhilNorman2/api-validation
#use existing origin -after first time through
git remote set-url origin https://github.com/PhilNorman2/api-validation
git push -f origin master

