import express from 'express';
import passport from 'passport';
import { signToken } from '../utils';
import { discourseLogin } from './sso.discourse.service';

const router = express.Router();

// @router   GET /api/auth/soo/discourse
// @desc     Single Sign On for Discourse forum
// @access   Private
router.get(
  '/',
  passport.authenticate('jwt'),
  async (req, res) => {
    let redirectUrl = null;

    if (req.query.sso && req.query.sig) {
      try {
        redirectUrl = discourseLogin(req.query.sso, req.query.sig, req.user);
      } catch (err) {
        console.error(err);
        return res.status(500).send('Error authenticating the user.');
      }
    }

    return res
      .status(200)
      .cookie('jwt', signToken(req.user), {
        httpOnly: true,
      })
      .json({redirectUrl: redirectUrl});
  }
);

module.exports = router;
