pipeline {
    agent any


    stages {
        stage('Cloner le repo') {
            steps {
                git url: 'git@github.com:sarrabarhoumi/lagos.git', credentialsId: 'ssh-key-jenkins'
            }
        }
        stage('Build Docker') {
            steps {
                sh 'docker-compose up --build -d'
            }
        }
    }
}
