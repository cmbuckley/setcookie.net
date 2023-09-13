#!/bin/bash

public="$1"
glob='(js|css)'
map="${0%.*}.map"

# clobber map
echo -n > "$map"

# delete assets
find "$public" -regextype posix-extended -regex ".*\.[a-f0-9]{32}\.$glob" -exec rm {} \;

# search and populate map
find "$public" -regextype posix-extended -regex ".*\.$glob" -printf "/%P\n" | while read asset; do
    md5=$(md5sum "$public$asset" | cut -f 1 -d ' ')
    fingerprint="${asset%.*}.$md5.${asset##*.}"

    cp "$public$asset" "$public${asset%.*}.$md5.${asset##*.}"
    echo "$asset,$fingerprint" >> "$map"
done
