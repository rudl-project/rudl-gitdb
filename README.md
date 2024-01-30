# rudl-gitdb
Rudl GitDb Microservice



## API Routes

***/hooks/repo/push

***/hooks/trigger?token={triggerToken>}**
Trigger a update on project repository update


## Configuration

### Environment Variables

| Name                  | Default      | Description     |
|-----------------------|--------------|-----------------|
| GIT_REPO_URL          |
| GIT_REPO_SSH_KEY      | The ssh private key to access the git repo (or: file:/path/to/secret) |
| RUDL_VAULT_SECRET     | The secret to open the vault. (or file:/path/to/secret_file) |
