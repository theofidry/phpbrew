#!/usr/bin/env fish
# Authors:
#   - Yo-An Lin
#   - Márcio Almada
#   - Rack Lin

# PHPBrew defaults:
# PHPBREW_HOME: contains the phpbrew config (for users)
# PHPBREW_ROOT: contains installed php(s) and php source files.
# PHPBREW_SKIP_INIT: if you need to skip loading config from the init file.
# PHPBREW_PHP:  the current php version.
# PHPBREW_PATH: the bin path of the current php.

# export alias for bourne shell compatibility.
# From PR https://github.com/fish-shell/fish-shell/pull/1833
if not functions --query export
  function export --description 'Set global variable. Alias for set -g, made for bash compatibility'
          if test -z "$argv"
             set
             return 0
          end
          for arg in $argv
              set -l v (echo $arg|tr '=' \n)
              set -l c (count $v)
              switch $c
                      case 1
                              set -gx $v $$v
                      case 2
                              set -gx $v[1] $v[2]
              end
          end
  end
end

[ -z "$PHPBREW_HOME" ]; and set -gx PHPBREW_HOME "$HOME/.phpbrew"

function __phpbrew_load_user_config
    # load user-defined config
    if [ -f $PHPBREW_HOME/init ]
        source $PHPBREW_HOME/init
        set -gx PATH $PHPBREW_PATH $PATH
    end
end

if [ -z "$PHPBREW_SKIP_INIT" ]
    __phpbrew_load_user_config
end

[ -z "$PHPBREW_ROOT" ]; and set -gx PHPBREW_ROOT "$HOME/.phpbrew"
[ -z "$PHPBREW_BIN" ]; and set -gx PHPBREW_BIN "$PHPBREW_HOME/bin"
[ -z "$PHPBREW_VERSION_REGEX" ]; and set -gx PHPBREW_VERSION_REGEX '^([[:digit:]]+\.){2}[[:digit:]]+(-dev|((alpha|beta|RC)[[:digit:]]+))?$'

[ ! -d "$PHPBREW_ROOT" ]; and mkdir $PHPBREW_ROOT
[ ! -d "$PHPBREW_HOME" ]; and mkdir $PHPBREW_HOME

[ ! -d $PHPBREW_BIN ]; and mkdir -p $PHPBREW_BIN

function __wget_as
    set -l url $argv[1]
    set -l target $argv[2]
    wget --no-check-certificate -c $url -O $target
end

function __phpbrew_set_lookup_prefix
    switch (echo $argv[1])
        case debian ubuntu linux
            # echo /usr/lib/x86_64-linux-gnu:/usr/lib/i386-linux-gnu
            echo /usr
        case macosx
            echo /usr
        case macports
            echo /opt/local
        case homebrew
            echo /usr/local/Cellar:/usr/local
        case '*'
            if [ -e $argv[1] ]
                echo $argv[1]
            else
                for dir in /opt/local /usr/local/Cellar /usr/local /usr
                    if [ -e $dir ]
                        echo $dir
                        return
                    end
                end
            end
    end
end

function phpbrew
    # Check bin/phpbrew if we are in PHPBrew source directory,
    # This is only for development
    if [ -e bin/phpbrew ]
        set -g BIN 'bin/phpbrew'
    else
        set -g BIN 'phpbrew'
    end

    set exit_status
    set short_option
    # export SHELL
    if [ (echo $argv[1] | awk 'BEGIN{FS=""}{print $1}') = '-' ]
        set short_option $argv[1]
        set -e argv[1]
    else
        set short_option ""
    end

    switch (echo $argv[1])
        case use
            if [ (count $argv) -eq 1 ]
                if [ -z "$PHPBREW_PHP" ]
                    echo "Currently using system php"
                else
                    echo "Currently using $PHPBREW_PHP"
                end
            else
                if begin ; [ ! -d "$PHPBREW_ROOT/php/$argv[2]" ]; and echo $argv[2] | egrep -q -e $PHPBREW_VERSION_REGEX; end
                    set _PHP_VERSION "php-$argv[2]"
                else
                    set _PHP_VERSION $argv[2]
                end

                # checking php version exists?
                set NEW_PHPBREW_PHP_PATH "$PHPBREW_ROOT/php/$_PHP_VERSION"
                if [ -d $NEW_PHPBREW_PHP_PATH ]
                    if [ $BIN = "phpbrew" ]
                        set code (command phpbrew env $_PHP_VERSION | grep -v '^#' | tr '\n' ';')
                    else
                        set code (eval $BIN env $_PHP_VERSION | grep -v '^#' | tr '\n' ';')
                    end
                    if [ -z "$code" ]
                        set exit_status 1
                    else
                        set exit_status 0
                        eval $code
                        __phpbrew_set_path
                    end
                else
                    echo "PHP version $_PHP_VERSION is not installed."
                end
            end
        case cd-src
            set -l SOURCE_DIR $PHPBREW_HOME/build/$PHPBREW_PHP
            if [ -d $SOURCE_DIR ]
                cd $SOURCE_DIR
            end
        case 'switch'
            if [ (count $argv) -eq 1 ]
                echo "Please specify the php version."
            else
                __phpbrew_reinit $argv[2]
            end
        case lookup-prefix
            if [ (count $argv) -eq 1 ]
                if [ -n "$PHPBREW_LOOKUP_PREFIX" ]
                    echo $PHPBREW_LOOKUP_PREFIX
                end
            else
                set -gx PHPBREW_LOOKUP_PREFIX (__phpbrew_set_lookup_prefix $argv[2])
                echo $PHPBREW_LOOKUP_PREFIX
                __phpbrew_update_config
            end
        case cd
            if [ (count $argv) -eq 1 ]; return 0; end

            switch $argv[2]
                case var
                    set chdir $PHPBREW_ROOT/php/$PHPBREW_PHP/var
                case etc
                    set chdir $PHPBREW_ROOT/php/$PHPBREW_PHP/etc
                case dist
                    set chdir $PHPBREW_ROOT/php/$PHPBREW_PHP
                case build
                    set chdir $PHPBREW_ROOT/build/$PHPBREW_PHP
                case '*'
                    echo "$argv[2] not found"
                    return 0
            end
            echo "Switching to $chdir, run 'cd -' to go back."
            cd $chdir
            return 0

        case fpm
            if [ (count $argv) -ge 3 ]
              set -g _PHP_VERSION $argv[3]
            else
              set -g _PHP_VERSION $PHPBREW_PHP
            end

            mkdir -p $PHPBREW_ROOT/php/$_PHP_VERSION/var/run
            set -g PHPFPM_BIN $PHPBREW_ROOT/php/$_PHP_VERSION/sbin/php-fpm
            set -g PHPFPM_PIDFILE $PHPBREW_ROOT/php/$_PHP_VERSION/var/run/php-fpm.pid

            function fpm_start
              echo "Starting php-fpm..."
              set -l regex '^php-5\.2.*'

              if [ (count $argv) -ge 4 ]
                set _PHPFPM_APPEND $argv[4..-1]
              else
                set _PHPFPM_APPEND ""
              end


              if echo $_PHP_VERSION | egrep -q -e $regex
                eval $PHPFPM_BIN start
              else
                 eval $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php.ini --fpm-config $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf --pid $PHPFPM_PIDFILE $_PHPFPM_APPEND
              end

              if [ "$status" != "0" ]
                echo "php-fpm start failed."
              end
            end

            function fpm_stop
              set -l regex '^php-5\.2.*'

              if echo $_PHP_VERSION | egrep -q -e $regex
                eval $PHPFPM_BIN stop
              else if [ -e $PHPFPM_PIDFILE ]
                echo "Stopping php-fpm..."
                kill (cat $PHPFPM_PIDFILE)
                rm -f $PHPFPM_PIDFILE
              end
            end

            [ (count $argv) -lt 2 ]; and $argv[2] = ''

            switch $argv[2]
              case start
                    fpm_start $argv
              case stop
                    fpm_stop
              case restart
                    fpm_stop
                    fpm_start $argv
              case module
                     eval $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php.ini --fpm-config $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf -m | less
              case info
                     eval $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php.ini --fpm-config $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf -i
              case config
                    if [ -n "$EDITOR" ]
                        eval $EDITOR $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf
                    else
                        echo "Please set EDITOR environment variable for your favor."
                        nano $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf
                    end
              case help
                     eval $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php.ini --fpm-config $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf --help
              case test
                     eval $PHPFPM_BIN --php-ini $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php.ini --fpm-config $PHPBREW_ROOT/php/$_PHP_VERSION/etc/php-fpm.conf --test
              case '*'
                    echo "Usage: phpbrew fpm [start|stop|restart|module|test|help|config]"
            end

        case env
            # we don't check php path here, you should check path before you
            # use env command to output the environment config.
            [ (count $argv) -ge 2 ]; and set -gx PHPBREW_PHP $argv[2]

            echo "export PHPBREW_ROOT=$PHPBREW_ROOT";
            echo "export PHPBREW_HOME=$PHPBREW_HOME";

            if [ -n "$PHPBREW_LOOKUP_PREFIX" ]
                echo "export PHPBREW_LOOKUP_PREFIX=$PHPBREW_LOOKUP_PREFIX";
            end

            if [ -n "$PHPBREW_PHP" ]
                echo "export PHPBREW_PHP=$PHPBREW_PHP";
                echo "export PHPBREW_PATH=$PHPBREW_ROOT/php/$PHPBREW_PHP/bin";
            end

        case off
            set -e PHPBREW_PHP
            set -e PHPBREW_PATH
            eval (eval $BIN env)
            __phpbrew_set_path
            echo "phpbrew is turned off."

        case switch-off
            set -e PHPBREW_PHP
            set -e PHPBREW_PATH
            eval (eval $BIN env)
            __phpbrew_reinit
            echo "phpbrew is switched off."

        case rehash
            echo "Rehashing..."
            source ~/.phpbrew/phpbrew.fish

        case purge
            if [ (count $argv) -ge 2 ]
              __phpbrew_remove_purge $argv[2] purge
            else
                if [ $BIN = "phpbrew" ]
                    command phpbrew BIN help
                else
                    eval $BIN help
                end
            end

        case '*'
            if [ $BIN = "phpbrew" ]
                if [ -z "$short_option" ]
                  command phpbrew $argv
                else
                  command phpbrew $short_option $argv
                end
            else
                if [ -z "$short_option" ]
                  eval $BIN $argv
                else
                  eval $BIN $short_option $argv
                end
            end
            set exit_status $status
            ;;
    end
    # hash -r
    return $exit_status
end

function __phpbrew_set_path
    functions --query php ; and functions -e php

    if [ -n "$PHPBREW_ROOT" ]
        begin; set -l NPATH;for i in $PATH; [ (expr "$i" : "$PHPBREW_ROOT") -eq 0 ]; and set NPATH $NPATH $i; end; set -gx PATH_WITHOUT_PHPBREW $NPATH;end;
    end

    if [ -z "$PHPBREW_PATH" ]
        set -gx PATH $PHPBREW_BIN $PATH_WITHOUT_PHPBREW
    else
        set -gx PATH $PHPBREW_PATH $PHPBREW_BIN $PATH_WITHOUT_PHPBREW
        return 0
    end
end

function __phpbrew_update_config
    set cmd $BIN env

    if [ (count $argv) -ge 1 ]
        if begin; [ ! -d "$PHPBREW_ROOT/php/$argv" ]; and echo $argv | egrep -q -e $PHPBREW_VERSION_REGEX ; end
            set -a cmd "php-$argv"
        else
            set -a cmd $argv
        end
    end

    begin
        echo "# DO NOT EDIT THIS FILE"
        command $cmd
    end > "$PHPBREW_HOME/init"

    source "$PHPBREW_HOME/init"
end

function __phpbrew_reinit
    __phpbrew_update_config $argv
    __phpbrew_set_path
end

function __phpbrew_remove_purge
    if [ (count $argv) -ge 1 ]
      set _PHP_VERSION $argv[1]
    end
    if [ "$_PHP_VERSION" = "$PHPBREW_PHP" ]
        echo "php version: $_PHP_VERSION is already in used."
        return 1
    end

    set _PHP_BIN_PATH $PHPBREW_ROOT/php/$_PHP_VERSION
    set _PHP_SOURCE_FILE $PHPBREW_ROOT/build/$_PHP_VERSION.tar.bz2
    set _PHP_BUILD_PATH $PHPBREW_ROOT/build/$_PHP_VERSION

    if [ -d $_PHP_BIN_PATH ]

        if begin; [ (count $argv) -ge 2 ]; and [ "$argv[2]" = "purge" ]; end
            rm -f $_PHP_SOURCE_FILE
            rm -fr $_PHP_BUILD_PATH
            rm -fr $_PHP_BIN_PATH
            echo "php version: $_PHP_VERSION is removed and purged."
        else
            rm -f $_PHP_SOURCE_FILE
            rm -fr $_PHP_BUILD_PATH

            for FILE1 in $_PHP_BIN_PATH/*
                if begin; [ "$FILE1" != "$_PHP_BIN_PATH/etc" ]; and [ "$FILE1" != "$_PHP_BIN_PATH/var" ]; end
                    rm -fr $FILE1
                end
            end

            echo "php version: $_PHP_VERSION is removed."
        end

    else
        echo "php version: $_PHP_VERSION not installed."
    end

    return 0
end

function phpbrew_current_php_version
  if type "php" > /dev/null
    set -l version (php -v | grep "PHP 5" | sed 's/.*PHP \([^-]*\).*/\1/' | cut -c 1-6)
    if [ -z "$PHPBREW_PHP" ]
      echo "php:$version-system"
    else
      echo "php:$version-phpbrew"
    end
  else
     echo "php:not-installed"
  end
end

if begin ; [ -n "$PHPBREW_SET_PROMPT" ]; and [ "$PHPBREW_SET_PROMPT" = "1" ]; end
    # export PS1="\w > \u@\h [$(phpbrew_current_php_version)]\n\\$ "
    # non supports in fish now
end

function _phpbrewrc_load --on-variable PWD --description 'Load configuration based on .phpbrewrc'
    set -q PHPBREW_RC_ENABLE
    or return

    status --is-command-substitution;
    and return

    set curr_dir "$PWD"
    set prev_dir ""
    set curr_fs 0
    set prev_fs 0

    while [ -n "$curr_dir" -a -d "$curr_dir" ]
        set prev_fs $curr_fs
        set curr_fs (stat -c %d "$curr_dir" ^/dev/null)  # GNU version
        if [ $status -ne 0 ]
            set curr_fs (stat -f %d "$curr_dir" >/dev/null ^&1)  # BSD version
        end

        # check if top level directory or filesystem boundary is reached
        if begin; [ "$curr_dir" = "/" ]; or [ -z "$PHPBREW_RC_DISCOVERY_ACROSS_FILESYSTEM" -a $prev_fs -ne 0 -a $curr_fs -ne $prev_fs ]; end
            # check if there's a previously loaded .phpbrewrc
            if [ ! -z "$PHPBREW_LAST_RC_DIR" ]
                set -e PHPBREW_LAST_RC_DIR
                __phpbrew_load_user_config
            end
            break
        end

        # check if .phpbrewrc present
        if [ -r "$curr_dir/.phpbrewrc" ]
            # check if it's not the same .phpbrewrc which was previously loaded
            if [ "$curr_dir" != "$PHPBREW_LAST_RC_DIR" ]
                __phpbrew_load_user_config
                set -g PHPBREW_LAST_RC_DIR "$curr_dir"
                source "$curr_dir/.phpbrewrc"
            end
            break
        end

        set curr_dir (dirname "$curr_dir")
    end
end

###
# phpbrew completions
###
function __fish_phpbrew_command
    set -l tokens (commandline -opc)
    test (count $tokens) -le 1; and return 1
    set -l command

    for token in $tokens[2..-1]
        switch $token
            case "-*"
            case "*"
                set -a command "$token"
        end
    end

    test (count $command) -eq 0; and return 1

    for token in $command
        echo $token
    end
end

function __fish_phpbrew_needs_command
    not __fish_phpbrew_command >/dev/null
end

function __fish_phpbrew_using_command
    set -l expected
    set -l position 0
    set -l multiple

    for arg in $argv
        switch $arg
            case "--position=*"
                string replace -- "--position=" "" "$arg" | read position
            case "--multiple"
                set multiple yes
            case "*"
                set -a expected "$arg"
        end
    end

    set -l exp_count (count $expected)
    set exp_count (math "$exp_count+$position")

    set -l actual (__fish_phpbrew_command)

    if [ -n "$multiple" ]
        if [ (count $actual) -lt $exp_count ]
            return 1
        end
    else
        if [ (count $actual) -ne $exp_count ]
            return 1
        end
    end

    set -l slice $actual[1..(count $expected)]

    test "$slice" = "$expected"
end

function __fish_phpbrew_arg_meta
    command phpbrew meta --flat $argv[1] arg $argv[2] $argv[3] | grep -v "^#"
end

# top level options
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -s v -l verbose -d "Print verbose message"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -s d -l debug -d "Print debug message"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -s q -l quiet -d "Be quiet"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -s h -l help -d "Show help"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -l version -d "Show version"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -s p -l profile -d "Display timing and memory usage information"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -l log-path -d "The path of a log file"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -l no-interact -d "Do not ask any interactive question"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -l no-progress -d "Do not display progress bar"

# commands
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a app -d "php app store"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a archive -d "Build executable phar file from composer.json"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a bash -d "This command generate a bash completion script automatically"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a cd -d "Change to directories"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a clean -d "Clean up the source directory of a PHP distribution"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a compile -d "compile current source into Phar format library file"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a config -d "Edit your current php.ini in your favorite $EDITOR"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a ctags -d "Run ctags at current php source dir for extension development"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a download -d "Download php"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a each -d "Iterate and run a given shell command over all php versions managed by PHPBrew"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a env -d "Export environment variables"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a ext -d "List extensions or execute extension subcommands"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a fpm -d "fpm commands"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a github:build-topics -d "Build topic classes from the wiki of a GitHub Project"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a help -d "Show help message of a command"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a info -d "Show current php information"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a init -d "Initialize phpbrew config file"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a install -d "Install php"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a known -d "List known PHP versions"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a list -d "List installed PHPs"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a list-ini -d "List loaded ini config files"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a meta -d "Return the meta data of a commands"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a off -d "Temporarily go back to the system php"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a path -d "Show paths of the current PHP"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a purge -d "Remove installed php version and config files"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a remove -d "Remove installed php build"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a self-update -d "Self-update, default to master version"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a switch -d "Switch default php version"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a switch-off -d "Definitely go back to the system php"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a update -d "Update PHP release source file"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a use -d "Use php, switch version temporarily"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a variants -d "List php variants"
complete -f -c phpbrew -n "__fish_phpbrew_needs_command" -a zsh -d "This function generate a zsh-completion script automatically"

# app
complete -f -c phpbrew -n "__fish_phpbrew_using_command app" -a get -d "Get PHP application"
complete -f -c phpbrew -n "__fish_phpbrew_using_command app" -a list -d "List PHP applications"

# app get
complete -x -c phpbrew -n "__fish_phpbrew_using_command app get" -l chmod -d "Set downloaded file mode"
complete -x -c phpbrew -n "__fish_phpbrew_using_command app get" -l downloader -d "Use alternative downloader"
complete -f -c phpbrew -n "__fish_phpbrew_using_command app get" -l continue -d "Continue getting a partially downloaded file"
complete -x -c phpbrew -n "__fish_phpbrew_using_command app get" -l http-proxy -d "HTTP proxy address"
complete -x -c phpbrew -n "__fish_phpbrew_using_command app get" -l http-proxy-auth -d "HTTP proxy authentication"
complete -x -c phpbrew -n "__fish_phpbrew_using_command app get" -l connect-timeout -d "Connection timeout"
complete -x -c phpbrew -n "__fish_phpbrew_using_command app get" -a "(__fish_phpbrew_arg_meta app.app.get 0 valid-values)" -d "Application name"

# archive
complete -x -c phpbrew -n "__fish_phpbrew_using_command archive" -s d -l working-dir -d "If specified, use the given directory as working directory"
complete -x -c phpbrew -n "__fish_phpbrew_using_command archive" -s c -l composer -d "The composer.json file"
complete -x -c phpbrew -n "__fish_phpbrew_using_command archive" -l vendor -d "Vendor directory name"
complete -f -c phpbrew -n "__fish_phpbrew_using_command archive" -l bootstrap -d "bootstrap or executable php file"
complete -f -c phpbrew -n "__fish_phpbrew_using_command archive" -l executable -d "make the phar file executable"
complete -f -c phpbrew -n "__fish_phpbrew_using_command archive" -s c -l compress -d "compress type: gz, bz2"
complete -f -c phpbrew -n "__fish_phpbrew_using_command archive" -l no-compress -d "do not compress phar file"
complete -f -c phpbrew -n "__fish_phpbrew_using_command archive" -l add -d "add a path respectively"
complete -f -c phpbrew -n "__fish_phpbrew_using_command archive" -l exclude -d "exclude pattern"
complete -f -c phpbrew -n "__fish_phpbrew_using_command archive" -l no-classloader -d "do not embed a built-in classloader in the generated phar file"
complete -f -c phpbrew -n "__fish_phpbrew_using_command archive" -l app-bootstrap -d "Include CLIFramework bootstrap script"

# bash
complete -x -c phpbrew -n "__fish_phpbrew_using_command bash" -l bind -d "bind complete to command"
complete -x -c phpbrew -n "__fish_phpbrew_using_command bash" -l program -d "programe name"

# cd
complete -x -c phpbrew -n "__fish_phpbrew_using_command cd" -a "(__fish_phpbrew_arg_meta cd 0 valid-values)"

# clean
complete -f -c phpbrew -n "__fish_phpbrew_using_command clean" -s a -l all -d "Remove all the files in the source directory of the PHP distribution"
complete -x -c phpbrew -n "__fish_phpbrew_using_command clean" -a "(__fish_phpbrew_arg_meta clean 0 valid-values)"

# compile
complete -f -c phpbrew -n "__fish_phpbrew_using_command compile" -l classloader -d "embed classloader source file"
complete -f -c phpbrew -n "__fish_phpbrew_using_command compile" -l bootstrap -d "bootstrap or executable source file"
complete -f -c phpbrew -n "__fish_phpbrew_using_command compile" -l executable -d "is a executable script \?"
complete -f -c phpbrew -n "__fish_phpbrew_using_command compile" -l lib -d "library path"
complete -f -c phpbrew -n "__fish_phpbrew_using_command compile" -l include -d "include path"
complete -f -c phpbrew -n "__fish_phpbrew_using_command compile" -l exclude -d "exclude pattern"
complete -x -c phpbrew -n "__fish_phpbrew_using_command compile" -l output -d "output"
complete -f -c phpbrew -n "__fish_phpbrew_using_command compile" -s c -l compress -d "phar file compress type: gz, bz2"
complete -f -c phpbrew -n "__fish_phpbrew_using_command compile" -l no-compress -d "do not compress phar file"

# ctags
complete -x -c phpbrew -n "__fish_phpbrew_using_command ctags" -a "(__fish_phpbrew_arg_meta ctags 0 valid-values)"

# download
complete -f -c phpbrew -n "__fish_phpbrew_using_command download" -s f -l force -d "Force extraction"
complete -f -c phpbrew -n "__fish_phpbrew_using_command download" -l old -d "enable old phps \(less than 5.3\)"
complete -x -c phpbrew -n "__fish_phpbrew_using_command download" -l mirror -d "Use mirror specific site"
complete -x -c phpbrew -n "__fish_phpbrew_using_command download" -l downloader -d "Use alternative downloader"
complete -f -c phpbrew -n "__fish_phpbrew_using_command download" -l continue -d "Continue getting a partially downloaded file"
complete -x -c phpbrew -n "__fish_phpbrew_using_command download" -l http-proxy -d "HTTP proxy address"
complete -x -c phpbrew -n "__fish_phpbrew_using_command download" -l http-proxy-auth -d "HTTP proxy authentication"
complete -x -c phpbrew -n "__fish_phpbrew_using_command download" -l connect-timeout -d "Connection timeout"
complete -x -c phpbrew -n "__fish_phpbrew_using_command download" -a "(__fish_phpbrew_arg_meta download 0 suggestions)"

# env
complete -f -c phpbrew -n "__fish_phpbrew_using_command env" -a "(__fish_phpbrew_arg_meta env 0 valid-values)"

# ext
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext" -s so -l show-options -d "Show extension configure options"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext" -s sp -l show-path -d "Show extension config.m4 path"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext" -a clean -d "Clean up the compiled objects in the extension source directory"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext" -a config -d "Edit extension-specific configuration file"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext" -a disable -d "Disable PHP extension"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext" -a enable -d "Enable PHP extension"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext" -a install -d "Install PHP extension"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext" -a known -d "List known versions"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext" -a show -d "Show information of a PHP extension"

# ext clean
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext clean" -s p -l purge -d "Remove all the source files"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext clean" -a "(__fish_phpbrew_arg_meta extension.clean 0 suggestions)"

# ext config
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext config" -a "(__fish_phpbrew_arg_meta extension.config 0 suggestions)"

# ext disable
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext disable" -a "(__fish_phpbrew_arg_meta extension.disable 0 suggestions)"

# ext enable
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext enable" -a "(__fish_phpbrew_arg_meta extension.enable 0 suggestions)"

# ext install
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext install" -l pecl -d "Try to download from PECL even when ext source is bundled with php-src"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext install" -l redownload -d "Force to redownload extension source even if it is already available"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext install" -l downloader -d "Use alternative downloader"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext install" -l continue -d "Continue getting a partially downloaded file"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext install" -l http-proxy -d "HTTP proxy address"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext install" -l http-proxy-auth -d "HTTP proxy authentication"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext install" -l connect-timeout -d "Connection timeout"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext install" -a "(__fish_phpbrew_arg_meta extension.install 0 suggestions)"

# ext known
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext known" -l downloader -d "Use alternative downloader"
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext known" -l continue -d "Continue getting a partially downloaded file"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext known" -l http-proxy -d "HTTP proxy address"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext known" -l http-proxy-auth -d "HTTP proxy authentication"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext known" -l connect-timeout -d "Connection timeout"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext known" -a "(__fish_phpbrew_arg_meta extension.known 0 suggestions)"

# ext show
complete -f -c phpbrew -n "__fish_phpbrew_using_command ext show" -l download -d "Download the extension source if extension not found"
complete -x -c phpbrew -n "__fish_phpbrew_using_command ext show" -a "(__fish_phpbrew_arg_meta extension.show 0 suggestions)"

# fpm
complete -f -c phpbrew -n "__fish_phpbrew_using_command fpm" -a restart -d "Restart FPM server"
complete -f -c phpbrew -n "__fish_phpbrew_using_command fpm" -a setup -d "Generate and setup FPM startup config"
complete -f -c phpbrew -n "__fish_phpbrew_using_command fpm" -a start -d "Start FPM server"
complete -f -c phpbrew -n "__fish_phpbrew_using_command fpm" -a stop -d "Stop FPM server"

# fpm setup
complete -f -c phpbrew -n "__fish_phpbrew_using_command fpm setup" -l systemctl -d "Generate systemd service entry"
complete -f -c phpbrew -n "__fish_phpbrew_using_command fpm setup" -l initd -d "Generate init.d script"
complete -f -c phpbrew -n "__fish_phpbrew_using_command fpm setup" -l launchctl -d "Generate plist for launchctl \(OS X\)"
complete -f -c phpbrew -n "__fish_phpbrew_using_command fpm setup" -l stdout -d "Print config to STDOUT instead of writing to the file"

# github:build-topics
complete -x -c phpbrew -n "__fish_phpbrew_using_command github:build-topics" -l ns -d "Class namespace"
complete -x -c phpbrew -n "__fish_phpbrew_using_command github:build-topics" -l dir -d "Output directory"
complete -f -c phpbrew -n "__fish_phpbrew_using_command github:build-topics" -l update -d "Update wiki repository"

# help
complete -f -c phpbrew -n "__fish_phpbrew_using_command help" -l dev -d "Show development commands"

# init
complete -x -c phpbrew -n "__fish_phpbrew_using_command init" -s c -l config -d "The YAML config file which should be copied into phpbrew home.The config file is used for creating custom virtual variants"
complete -x -c phpbrew -n "__fish_phpbrew_using_command init" -l root -d "Override the default PHPBREW_ROOT path setting.This option is usually used to load system-wide build pool"

# install
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l test -d "Run tests after the installation"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -l name -d "The name of the installation"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -l mirror -d "Use specified mirror site"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l post-clean -d "Run make clean after the installation"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l production -d "Use production configuration file"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -l build-dir -d "Specify the build directory"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l root -d "Specify PHPBrew root instead of PHPBREW_ROOT"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l home -d "Specify PHPBrew home instead of PHPBREW_HOME"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l no-config-cache -d "Do not use config.cache for configure script"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l no-clean -d "Do not clean previously compiled objects before building PHP"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l no-patch -d "Do not apply any patch"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l no-configure -d "Do not run configure script"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l no-install -d "Do not install, just run build the target"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -s n -l nice -d "Runs build processes at an altered scheduling priority"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l patch -d "Apply patch before build"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l old -d "Install phpbrew incompatible phps \(< 5.3\)"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l user-config -d "Allow users create their own config file \(php.ini or extension config init files\)"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -l downloader -d "Use alternative downloader"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l continue -d "Continue getting a partially downloaded file"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -l http-proxy -d "HTTP proxy address"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -l http-proxy-auth -d "HTTP proxy authentication"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -l connect-timeout -d "Connection timeout"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -s f -l force -d "Force the installation \(redownloads source\)"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -s d -l dryrun -d "Do not build, but run through all the tasks"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -l like -d "Inherit variants from an existing build"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -s j -l jobs -d "Specifies the number of jobs to run build simultaneously \(make -jN\)"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l stdout -d "Outputs install logs to stdout"
complete -f -c phpbrew -n "__fish_phpbrew_using_command install" -l sudo -d "sudo to run install command"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install" -a "(__fish_phpbrew_arg_meta install 0 suggestions)"
complete -x -c phpbrew -n "__fish_phpbrew_using_command install --position=1 --multiple" -a "(__fish_phpbrew_arg_meta install 1 suggestions)"

# known
complete -f -c phpbrew -n "__fish_phpbrew_using_command known" -s m -l more -d "Show more older versions"
complete -f -c phpbrew -n "__fish_phpbrew_using_command known" -s o -l old -d "List old phps \(less than 5.3\)"
complete -f -c phpbrew -n "__fish_phpbrew_using_command known" -s u -l update -d "Update release list"
complete -x -c phpbrew -n "__fish_phpbrew_using_command known" -l downloader -d "Use alternative downloader"
complete -f -c phpbrew -n "__fish_phpbrew_using_command known" -l continue -d "Continue getting a partially downloaded file"
complete -x -c phpbrew -n "__fish_phpbrew_using_command known" -l http-proxy -d "HTTP proxy address"
complete -x -c phpbrew -n "__fish_phpbrew_using_command known" -l http-proxy-auth -d "HTTP proxy authentication"
complete -x -c phpbrew -n "__fish_phpbrew_using_command known" -l connect-timeout -d "Connection timeout"

# list
complete -f -c phpbrew -n "__fish_phpbrew_using_command list" -s d -l dir -d "Show php directories"
complete -f -c phpbrew -n "__fish_phpbrew_using_command list" -s v -l variants -d "Show used variants"

# meta
complete -f -c phpbrew -n "__fish_phpbrew_using_command meta" -l flat -d "flat list format"
complete -f -c phpbrew -n "__fish_phpbrew_using_command meta" -l zsh -d "output for zsh"
complete -f -c phpbrew -n "__fish_phpbrew_using_command meta" -l bash -d "output for bash"
complete -f -c phpbrew -n "__fish_phpbrew_using_command meta" -l json -d "output in JSON format \(un-implemented\)"

# path
complete -x -c phpbrew -n "__fish_phpbrew_using_command path" -a "(__fish_phpbrew_arg_meta path 0 valid-values)"

# purge
complete -x -c phpbrew -n "__fish_phpbrew_using_command purge --multiple" -a "(__fish_phpbrew_arg_meta purge 0 valid-values)"

# remove
complete -x -c phpbrew -n "__fish_phpbrew_using_command remove" -a "(__fish_phpbrew_arg_meta remove 0 valid-values)"

# self-update
complete -x -c phpbrew -n "__fish_phpbrew_using_command self-update" -l downloader -d "Use alternative downloader"
complete -f -c phpbrew -n "__fish_phpbrew_using_command self-update" -l continue -d "Continue getting a partially downloaded file"
complete -x -c phpbrew -n "__fish_phpbrew_using_command self-update" -l http-proxy -d "HTTP proxy address"
complete -x -c phpbrew -n "__fish_phpbrew_using_command self-update" -l http-proxy-auth -d "HTTP proxy authentication"
complete -x -c phpbrew -n "__fish_phpbrew_using_command self-update" -l connect-timeout -d "Connection timeout"
complete -x -c phpbrew -n "__fish_phpbrew_using_command self-update" -a "(__fish_phpbrew_arg_meta self-update 0 suggestions)"

# switch
complete -x -c phpbrew -n "__fish_phpbrew_using_command switch" -a "(__fish_phpbrew_arg_meta switch 0 valid-values)"

# update
complete -f -c phpbrew -n "__fish_phpbrew_using_command update" -s o -l old -d "List old phps \(less than 5.3\)"
complete -f -c phpbrew -n "__fish_phpbrew_using_command update" -l official -d "Unserialize release information from official site \(using `unserialize` function\)"
complete -x -c phpbrew -n "__fish_phpbrew_using_command update" -l downloader -d "Use alternative downloader"
complete -f -c phpbrew -n "__fish_phpbrew_using_command update" -l continue -d "Continue getting a partially downloaded file"
complete -x -c phpbrew -n "__fish_phpbrew_using_command update" -l http-proxy -d "HTTP proxy address"
complete -x -c phpbrew -n "__fish_phpbrew_using_command update" -l http-proxy-auth -d "HTTP proxy authentication"
complete -x -c phpbrew -n "__fish_phpbrew_using_command update" -l connect-timeout -d "Connection timeout"

# use
complete -x -c phpbrew -n "__fish_phpbrew_using_command use" -a "(__fish_phpbrew_arg_meta use 0 valid-values)"

# zsh
complete -x -c phpbrew -n "__fish_phpbrew_using_command zsh" -l bind -d "bind complete to command"
complete -x -c phpbrew -n "__fish_phpbrew_using_command zsh" -l program -d "programe name"
