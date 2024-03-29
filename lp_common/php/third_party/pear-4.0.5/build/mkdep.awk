#  +----------------------------------------------------------------------+
#  | PHP version 4.0                                                      |
#  +----------------------------------------------------------------------+
#  | Copyright (c) 2000 The PHP Group                                     |
#  +----------------------------------------------------------------------+
#  | This source file is subject to version 2.02 of the PHP license,      |
#  | that is bundled with this package in the file LICENSE, and is        |
#  | available at through the world-wide-web at                           |
#  | http://www.php.net/license/2_02.txt.                                 |
#  | If you did not receive a copy of the PHP license and are unable to   |
#  | obtain it through the world-wide-web, please send a note to          |
#  | license@php.net so we can mail you a copy immediately.               |
#  +----------------------------------------------------------------------+
#  | Authors: Sascha Schumann <sascha@schumann.cx>                        |
#  +----------------------------------------------------------------------+
#
# $Id: mkdep.awk,v 1.1.1.1 2002/06/19 00:15:30 gente_libre Exp $
#
# Usage:
#
# echo top_srcdir top_builddir srcdir CPP [CPP-ARGS] filenames | \
#      awk -f mkdep.awk > dependencies


{
	top_srcdir=$1
	top_builddir=$2
	srcdir=$3
	cmd=$4

	for (i = 5; i <= NF; i++) {
		if (match($i, "^-[A-Z]") == 0)
			break;
		cmd=cmd " " $i
	}

	dif=i-1
		
	for (; i <= NF; i++)
		filenames[i-dif]=$i
	
	no_files=NF-dif
	
	for(i = 1; i <= no_files; i++) {
		if (system("test -r " filenames[i]) != 0)
			continue
		
		target=filenames[i]
		sub(srcdir "/", "", target)
		target2=target
		sub("\.(c|cpp)$", ".lo", target);
		sub("\.(c|cpp)$", ".slo", target2);

		for (e in used)
			delete used[e]
		
		cmdx=cmd " " filenames[i]
		done=0
		while ((cmdx | getline) > 0) {
			if (match($0, "^# [0-9]* \".*\.h\"") != 0) {
				if (sub(top_srcdir, "$(top_srcdir)", $3) == 0)
					sub(top_builddir, "$(top_builddir)", $3)
				if (substr($3,2,1) != "/" && used[$3] != 1) {
					if (done == 0)
						printf(target " " target2 ":")
					done=1
					printf(" \\\n\t" substr($3,2,length($3)-2))
					used[$3] = 1;
				}	
			}
		}
		if (done == 1)
			print "\n"
	}
} 
