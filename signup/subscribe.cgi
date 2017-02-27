#!/usr/bin/perl
# Be sure that the line above points to where perl 5 is 
# on your system.

##################################################################
# subscribe.cgi: subscription e-mail collector with subscribe
# and unsubscribe features.
# Release 1.0 on 04/17/99
# (C) 1999 BigNoseBird.Com, Inc. This program is freeware and may 
# be used at no cost to you (just leave this notice intact). 
# Feel free to modify, hack, and play  with this script. 
# It is provided AS-IS with no warranty of any kind.                
# We also cannot assume responsibility for either any programs      
# provided here, or for any advice that is given since we have no   
# control over what happens after our code or words leave this site.
# Always use prudent judgment in implementing any program- and      
# always make a backup first!                                       
#
##################################################################

# When calling the script, you must provide the following INPUTs
#
#   datafile  (name of file that will contain the addresses.)
#   email  (the e-mail address of the person subscribing/unsubscribing
#   action (subscribe or unsubscribe)

#### USER CONFIGURATION SECTION ##################################

# set BASEDIR to the directory that will hold your letter and
# mailling list files. Be certain that the script has permission
# to write to this directory. This must be set to the same value
# you declare in nmmdadmin.cgi

   $BASEDIR="/homepages/21/d123799337/htdocs/signup/";

# remove the # mark before okaydomains to restrict subscription
# requests to your web site. This prevents others from calling
# your script from elsewhere. If you encourage others to offer
# your newsletter from their sites, do NOT remove the # mark.

 #  @okaydomains=("http://letwGrafixMania.com", "http://www.letw.com");     

# $delimiter is the special character that is used to separate the
# items of information about each e-mail address. To use the TAB
# character, uncomment (remove the # mark) the line that says TAB
# and place a # mark at the start of the line that says PIPE.

   $delimiter="\\|"; #PIPE
#   $delimiter="\\t"; #TAB

##################################################################

   $lockfile="/tmp/subscribe.lck";

   &valid_page;
   &decode_vars;
   $return_to=$ENV{'HTTP_REFERER'};
   $the_date=localtime();
   $ip_addr=$ENV{'REMOTE_ADDR'};
   $datafile="$fields{'datafile'}\.mbz";
   $email=$fields{'email'};
   $action=$fields{'action'};

   if ($datafile eq "")
     { print "Content-type: text/html\n\n";
       print "Configuration Error: No datafile specified\n";
       exit;
     }
   if ($action eq "")
     { print "Content-type: text/html\n\n";
       print "Configuration Error: No action specified\n";
       exit;
     }

   if (&valid_address($email) == 0)   
     {
      &bad_email;
      exit;
     }

   &write_data;
   &thank_you;

sub thank_you
{
  if ($action eq "unsubscribe")
    { $whichaction = "removed from";}
    else { $whichaction = "added to";}
print "Content-type: text/html\n\n";
print <<__END_THANKS__;
<CENTER>
&nbsp;<P>
&nbsp;<P>

<table border="0" width="60%" cellspacing="0" cellpadding="0">
  <tr>
    <td width="100%" align="center"><font face="Bahamas" size="5">Thank you for
      subscribing !!</font>
      <p>
      <p>&nbsp;</td>
  </tr>
</table>
      <B>Your e-mail address has been $whichaction our mailing list.<BR>
      Please click on the link below to return <BR>
      to the page you were last on.
      <P>
      <A HREF="javascript:history.back(1)"><B>BACK</B></A></B>
      <P>
      <FONT SIZE=1><B> 
      LAGourmetpizza.com</B><BR>
     </FONT>
      <P>
      &nbsp;
    </TD>
   </TR>
  </TABLE>
  </TD>
 </TR>
</TABLE>
</CENTER>
__END_THANKS__
}

##################################################################
sub write_data
{
   &get_datetime;
   $delim=$delimiter;
   $delim=~s/\\//g;
   &get_the_lock; 
   open(IDBFILE,"<$BASEDIR$datafile");
   @mailing=<IDBFILE>;            
   close(IDBFILE);             

   open(ODBFILE,">$BASEDIR$datafile");
   foreach $line (@mailing)    
    {
      chop $line;
      ($thismail,$thisip,$thisdate)=split(/$delimiter/,$line);
      if ($email ne $thismail)
       {
        print ODBFILE "$line\n";
       }
    }
   if ($action eq "subscribe")
    {
     print ODBFILE "$email$delim$ip_addr$delim$rd$delim\n";
    }
   close (ODBFILE);
# This may seem a silly place to check to see if we were able to
# create the file, but a lot of people don't have access to their
# error logs to find message written by "die"
   &drop_the_lock;
   if (!-w "$BASEDIR$datafile")
     { print "Content-type: text/html\n\n";
       print "Configuration Error: could not create datafile 
              please check path and permissions!\n";
       exit;
     }

}

##################################################################
sub decode_vars
 {
  $i=0;
  read(STDIN,$temp,$ENV{'CONTENT_LENGTH'});
  @pairs=split(/&/,$temp);
  foreach $item(@pairs)
   {
    ($key,$content)=split(/=/,$item,2);
    $content=~tr/+/ /;
    $content=~s/%(..)/pack("c",hex($1))/ge;
    $content=~s/\t/ /g;
    $content=~tr/A-Z/a-z/;
    $fields{$key}=$content;
   }
}

##################################################################
sub valid_address
 {
  local($testmail) = @_;
  if ($testmail eq "")
   {return 0;}
  if ($testmail =~ /(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/ ||
  $testmail !~ /^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?)$/)
   { return 0;}
   else 
    { return 1;}
}

##################################################################
sub bad_email
{
print <<__STOP_OF_BADMAIL__;
Content-type: text/html

<FONT SIZE="+1">
<B>
SORRY! Your request could not be processed because of an
improperly formatted e-mail address. Please use your browser's 
back button to return to the form entry page.
</B>
</FONT>
__STOP_OF_BADMAIL__
}

##################################################################
sub get_the_lock
{
  local ($endtime);                                   
  $endtime = 60;                                      
  $endtime = time + $endtime;                         
  while (-e $lockfile && time < $endtime) 
   {
    # Do Nothing                                    
   }                                                   
   open(LOCK_FILE, ">$lockfile");                     
}

##################################################################
sub drop_the_lock
{
  close($lockfile);
  unlink($lockfile);
}

##################################################################
sub get_datetime
{
   %mos = ( "jan","01", "feb","02", "mar","03", "apr","04",             
            "may","05", "jun","06", "jul","07", "aug","08", 
            "sep","09", "oct","10", "nov","11", "dec","12");
   $date=localtime(time);                                               
   ($day, $month, $num, $time, $year) = split(/\s+/,$date);             
   @time_temp=split(/\:/,$time);                                        
   $month=~tr/A-Z/a-z/;                                                 
   $rd="$mos{$month}\/$num\/$year $time_temp[0]\:$time_temp[1]\:$time_temp[2]";
}


##################################################################
sub valid_page
 {
  if (@okaydomains == 0)
     {return;}
  $DOMAIN_OK=0;                                         
  $RF=$ENV{'HTTP_REFERER'};                             
  $RF=~tr/A-Z/a-z/;                                     
  foreach $ts (@okaydomains)                            
   {                                                    
     if ($RF =~ /$ts/)                                  
      {                                                 
        $DOMAIN_OK=1;                                   
      }                                                 
   }                                                    
   if ( $DOMAIN_OK == 0)                                
     {                                                  
      print "Content-type: text/html\n\n Sorry....Cant run from here!";    
      exit;                                             
     }                                                  
 }

