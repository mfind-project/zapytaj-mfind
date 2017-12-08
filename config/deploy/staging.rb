set :stage, :staging
server '46.248.188.98', user: 'zapytaj-t', port: '1986', roles: %w(app db web notify)
set :deploy_to, "/var/www/zapytaj-testing"
set :user, 'zapytaj-t'

ask :branch, 'develop'
