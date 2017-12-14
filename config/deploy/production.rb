set :stage, :production
server '46.248.188.98', user: 'zapytaj', port: '1986', roles: %w[app db web notify]
set :deploy_to, '/var/www/zapytaj'
set :user, 'zapytaj'

ask :branch, proc { `git describe --tags $(git rev-list --tags --max-count=1)`.chomp }
