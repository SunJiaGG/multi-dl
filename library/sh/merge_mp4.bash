#!/usr/bin/env bash
tmpfile=$(mktemp merge_mp4.XXXXXXXX)
IFS=$'\n';
for f in `ls $1`
	do echo "file '$PWD/$1/$f'" >> $tmpfile
done

ffmpeg -y -f concat -i $tmpfile  -bsf:a aac_adtstoasc -c copy "$PWD/$2"
rm $tmpfile
