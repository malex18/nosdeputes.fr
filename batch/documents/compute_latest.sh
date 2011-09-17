#!/bin/bash

if ! test -e out/ ; then
	mkdir out
fi

for file in `perl download_docs.pl`; do
  echo $file
  file2=`echo $file | sed 's/^\(pjl\|ppl\|ppr\|rap\|rga\|tas\)\//out\//'`
  perl parse_doc.pl $file > $file2
done;


