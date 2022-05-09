db.createUser({
	user: 'system',
	pwd: 'systemUser',
	roles: [
		{
			role: 'readWrite',
			db: 'gateway',
		},
	],
});
