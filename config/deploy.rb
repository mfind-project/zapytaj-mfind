set :application, 'zapytaj_mfind_pl'
set :repo_url, 'git@github.com:mfind-project/zapytaj-mfind.git'
set :scm, :git
set :pty, true
set :format, :pretty
set :keep_releases, 2

set :linked_files, fetch(:linked_files, [
  'zapytaj/config.ini'
])
