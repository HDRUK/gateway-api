import passport from 'passport'
import passportOidc from 'passport-openidconnect'
import { to } from 'await-to-js'

import { getUserByProviderId } from '../../user/user.repository'
import { getObjectById } from '../../tool/data.repository'
import { updateRedirectURL } from '../../user/user.service'
import { createUser } from '../../user/user.service'
import { signToken } from '../utils'
import { ROLES } from '../../user/user.roles'
import  queryString from 'query-string';
import  Url from 'url';
import { discourseLogin } from '../sso/sso.discourse.service'; 

const OidcStrategy = passportOidc.Strategy
const baseAuthUrl = process.env.AUTH_PROVIDER_URI;
const eventLogController = require('../../eventlog/eventlog.controller');

const strategy = app => {
    const strategyOptions = {
        issuer: baseAuthUrl,
        authorizationURL: baseAuthUrl + "/oidc/auth",
        tokenURL: baseAuthUrl + "/oidc/token",
        userInfoURL: baseAuthUrl + "/oidc/userinfo",
        clientID: process.env.openidClientID,
        clientSecret: process.env.openidClientSecret,
        callbackURL: `/auth/oidc/callback`,
        proxy: true
    }

    const verifyCallback = async (
        accessToken,
        refreshToken,
        profile,
        done 
    ) => {
        if (!profile || !profile._json || !profile._json.eduPersonTargetedID || profile._json.eduPersonTargetedID === '') return done("loginError");
        
        let [err, user] = await to(getUserByProviderId(profile._json.eduPersonTargetedID))
        if (err || user) {
            return done(err, user)
        }
        
        const [createdError, createdUser] = await to(
            createUser({
                provider: 'oidc',
                providerId: profile._json.eduPersonTargetedID,
                firstname: '',
                lastname: '',
                email: '',
                password: null,
                role: ROLES.Creator
            })
        )

        return done(createdError, createdUser)
    }

    passport.use('oidc', new OidcStrategy(strategyOptions, verifyCallback))

    app.get(
        `/auth/oidc`,
        (req, res, next) => {
            // Save the url of the user's current page so the app can redirect back to it after authorization
            if (req.headers.referer) {req.param.returnpage = req.headers.referer;}
            next();
        },
        passport.authenticate('oidc')
    )
    
    app.get('/auth/oidc/callback', (req, res, next) => {
        passport.authenticate('oidc', (err, user, info) => {
            if (err || !user) {
                //loginError
                if (err === 'loginError') return res.status(200).redirect(process.env.homeURL+'/loginerror')
                
                // failureRedirect
                var redirect = '/';
                let returnPage = null;

                if (req.param.returnpage) {
                    returnPage = Url.parse(req.param.returnpage);
                    redirect = returnPage.path;
                    delete req.param.returnpage;
                }
                
                let redirectUrl = process.env.homeURL + redirect;

                return res
                    .status(200)
                    .redirect(redirectUrl)
            }

            req.login(user, async (err) => {
                if (err) {
                    return next(err);
                }

                var redirect = '/';

                let returnPage = null;
                let queryStringParsed = null;
                if (req.param.returnpage) {
                    returnPage = Url.parse(req.param.returnpage);
                    redirect = returnPage.path;
                    queryStringParsed = queryString.parse(returnPage.query);
                }

                let [profileErr, profile] = await to(getObjectById(req.user.id))

                if (!profile) {
                    await to(updateRedirectURL({ id: req.user.id, redirectURL: redirect }))
                    return res.redirect(process.env.homeURL + '/completeRegistration/' + req.user.id)
                }

                if (req.param.returnpage) {
                    delete req.param.returnpage;
                }

                let redirectUrl = process.env.homeURL + redirect;

                if (queryStringParsed && queryStringParsed.sso && queryStringParsed.sig) {
                    try {
                        redirectUrl = discourseLogin(queryStringParsed.sso, queryStringParsed.sig, req.user);
                    } catch (err) {
                        console.error(err);
                        return res.status(500).send('Error authenticating the user.');
                    }
                }

                //Build event object for user login and log it to DB
                let eventObj = {
                    userId: req.user.id, 
                    event: `user_login_${req.user.provider}`,
                    timestamp: Date.now()
                }
                await eventLogController.logEvent(eventObj);

                return res
                .status(200)
                .cookie('jwt', signToken({_id: req.user._id, id: req.user.id, timeStamp: Date.now()}), {
                    httpOnly: true
                })
                .redirect(redirectUrl)

            });
        })(req, res, next);
    });

    return app
}

export { strategy }