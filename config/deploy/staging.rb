set :stage, :staging
server 'wordpress.stg.mfind.pl', user: 'zapytaj', port: '7324', roles: %w[app db web notify]
set :deploy_to, '/home/zapytaj'
set :user, 'zapytaj'

set :branch, 'develop'
