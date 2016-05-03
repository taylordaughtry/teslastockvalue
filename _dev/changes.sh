#!/bin/bash

# Start location
startDir="../"

# Beanstalk revision file
revision=".revision"

# Included directories
includes=(admin)
includes+=(public_html)

# Excluded directories
excludes=(craft/storage)
excludes+=(public_html/uploads)

# End of customizable settings

# Set up include/exclude strings

for i in ${includes[@]}; do
	includeString+="$startDir${i} "
done

count=0

for i in ${excludes[@]}; do
	if [ "$count" -gt 0 ]
	then
		excludeString+="|"
	fi

	excludeString+="\./$i/"
	count=$((count + 1))
done

# Escape slashes for the egrep later
excludeString=${excludeString//\//\\\/}

# See if we have a value from the command line. If not, check for a beanstalk .revision
# and get its last modified date.
if [ -z "$1" ]
then
	if [ ! -f $startDir$revision ]
	then
		echo 'No date provided and no Beanstalk .revision found. Exiting.'
		exit 0
	else
		dateStamp=$(stat -c %Z $startDir$revision)
	fi
else
	dateStamp=$(date --date "$1" +%s)
fi

# Convert date to # of days ago
dateNow=$(date +%s)

if [ "$dateStamp" -gt "$dateNow" ]
then
    echo "Starting date cannot be in the future"
    exit 0
fi

# Get the number of days since specified date, modifying slightly to
# exclude Beanstalk revision file if it is used
dateDiff=$((dateNow - dateStamp))
daysAgo=$(bc -l <<< "($dateDiff/86400)-.00001")

find $includeString -type f -mtime -$daysAgo | egrep -v "($excludeString)"