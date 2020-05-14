import express from 'express'
import { to } from 'await-to-js'
import { verifyPassword } from '../auth/utils'
import { login } from '../auth/strategies/jwt'
import { getUserByEmail } from '../user/user.repository'
import { getRedirectUrl } from '../auth/utils'
import passport from "passport";

const router = express.Router()

// @router   POST /api/v1/auth/login
// @desc     login user
// @access   Public
router.post('/login', async (req, res) => {
    const { email, password } = req.body

    const [err, user] = await to(getUserByEmail(email))

    const authenticationError = () => {
        return res
            .status(500)
            .json({ success: false, data: "Authentication error!" })
    }

    if (!(await verifyPassword(password, user.password))) {
        console.error('Passwords do not match')
        return authenticationError()
    }

    const [loginErr, token] = await to(login(req, user))

    if (loginErr) {
        console.error('Log in error', loginErr)
        return authenticationError()
    }

    return res
        .status(200)
        .cookie('jwt', token, {
            httpOnly: true
        })
        .json({
            success: true,
            data: getRedirectUrl(req.user.role)
        })

});

// @router   POST /api/v1/auth/logout
// @desc     logout user
// @access   Private
router.get('/logout', function (req, res) {
    req.logout();
    res.clearCookie('jwt');
    return res.json({ success: true });
});

// @router   GET /api/auth/status
// @desc     Return the logged in status of the user and their role.
// @access   Private
router.get(
'/status',
passport.authenticate('jwt'),
async (req, res) => {
    if (req.user) {
        return res.json({ success: true, data: [{ role: req.user.role, id: req.user.id, name: req.user.firstname + " " + req.user.lastname }] });
    } else {
        return res.json({ success: true, data: [{ role: "Reader", id: null, name: null }] });
    }
});
  
module.exports = router