#!/usr/bin/perl -w
#--------------------------------------------------------------------
# Plugin server designed for PandoraFMS (pandorafms.com)
# Checks for Pandora FMS WP (v2)
#
# Copyright (C) 2021 slerena@pandorafms.com
#--------------------------------------------------------------------

use strict;

my $numArgs = $#ARGV + 1;

if ($numArgs < 2){
    print "\nI need two parametres: FQN API \n";
    print "For example http://pandorafms.com online \n\n";
    exit 1;
}

my $fqn = $ARGV[0];
my $api = $ARGV[1];

# remove ending / just in case, WP REST API dont like it.
$fqn =~ s/\/$//;

my $command = "curl $fqn/wp-json/pandorafms_wp/$api";
my $output = `curl $fqn/wp-json/pandorafms_wp/$api 2> /dev/null`;
print $output;
exit 0;
