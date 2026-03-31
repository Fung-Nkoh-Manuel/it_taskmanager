pipeline {
    agent any
    
    triggers {
        pollSCM('H/5 * * * *')
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        stage('Debug - Test WSL Connection') {
            steps {
                withCredentials([string(credentialsId: 'server-password', variable: 'TEST_PASS')]) {
                    bat """
                        wsl bash -c "echo 'Testing connection...' && sshpass -p ${TEST_PASS} ssh -o StrictHostKeyChecking=no webadmin@10.13.14.164 'echo SSH works'"
                    """
                }
            }
        }
                stage('Deploy via WSL') {
            steps {
                withCredentials([string(credentialsId: 'server-password', variable: 'ANSIBLE_PASS')]) {
                    bat """
                        wsl bash -c "cd /mnt/c/xampp/htdocs/ansible && ansible-playbook -i inventory/hosts.ini site.yml --user=webadmin --extra-vars 'ansible_password=${ANSIBLE_PASS} ansible_become_password=${ANSIBLE_PASS}'"
                    """
                }
            }
        }
    }
}