
# Basic idea

Well, ever wondered how nice it would be to have *direct eye contact* with someone when having conversation  over the network?  There is bad news and good news for you.  *Bad news:* as long as we use web cameras to get our image to the other side, it is not possible. You can look straight into the camera, but you will not be able to focus on what's happening on the screen (which is the other side).  However, there is *good news:* you can (and probably want to) switch to using avatars, rather than presenting your real face, ugly and nasty as it is. 

The code in this project is the 1st step towards this goal. It *(1)* captures eye movements using Tobii tracker and *(2)* plots them on a simple avatar (just eye sockets for now) on the other side. 

Early tests show that it is working very well.  For example, ever *stared someone down*?  Or been stared down at?  Given the consentration, your pupils become really really still.  Well, I manage to achieve the same affect with this code. s

Note that this is the key component needed to achieve the final goal -- the all so distant goal of *direct eye contact*.

# Running the code on Sending side

On *sending* side it needs to run in command line using PHP.  It also needs Tobii tracker (I used 4C) and -- this is important -- have `GazeTrackEyeXGazeStream.exe` available at a path that you can find in `app.php`. I easily managed to find it.  I cannot share it here for obvious reasons.  Without it, no access to XY coordinates of what you are looking at on the screen.  Once you have all the components, make sure you properly calibrate your Tobii -- again, you should know what it is and how to do it if you want to run this code. 

On *sending side* you need to have 2 threads running.  Run the web server with something like `php -S 127.0.0.1:80 -t .` and WebSocket server with `php app.php run`. 

Wait. Before you `php app.php run`, you need to calibrate again -- this time using `php app.php tobiicalibrate`. This will run Tobii poller in the background, and show you the *margin* -- min, max values for X, Y.   Then just stare (Tobii should be running -- red lights on) at various corners of the screen to tell the script how far your gaze will go.  This is necessary to be able to convert actual values later on in to percentages.  

When done, find the line `var tobii = { xmin:....}` in `index.html` and edit the margin values. 

Now, to `pkill GazeTrackEyeXGazeStream` and `pkill php` just to make sure -- especially the poller keeps running in the background all the time.  This is a quick way to clean it up. 

By the way, I was running all this in `Cygwin` on Windows.  Runs without any problems. 


# Running the code on Receiving side

On *receiving* side, you have Firefox (most current browsers support WebSockets)  and need only `index.html` that you get from the web server above (127.0.0.1 in URL window in the default config). 

The contents for `index.html` are built automatically -- I have a script that puts all my JS libraries into a single (either JS or) HTML file.   So, `01.html` and `02.app.js` are never run directly -- they are part of the `index.html`.  However, you can see what it is them -- their code is short and all nice-looking and then find the respective lines in the `index.html`.  

Note that, if you plan to run on different macines, you need to change the IP address in `index.html` for the WebSocket server and enter another address in the URL in browser. 

The code in the browser outputs coordinates and debug messages into `console`, so have it shown.  The webapp inits WebSocket, sleeps for 3s, then builds the black face with white eye sockets and pupits.  Then, once the data starts streaming from the other side, pupits star moving.  See `test.mp4` for an example. 


That is all for now. 



