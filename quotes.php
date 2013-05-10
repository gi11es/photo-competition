<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

	function RandomQuote() {
		srand(time());
		$random = (rand(0, 30));

		switch ($random) {
			case 0 :
				$result = "To the complaint, \"There are no people in these photographs\", I respond, \"There are always two people: the photographer and the viewer\".<br/><i>Ansel Adams (1902 - 1984)</i>";
				break;
			case 1 :
				$result = "A photograph is a secret about a secret. The more it tells you the less you know.<br/><i>Diane Arbus (1923 - 1971)</i>";
				break;
			case 2 :
				$result = "Best wide-angle lens? Two steps backward. Look for the \"ah-ha\".<br/><i>Ernst Haas (1921 - 1986)</i>";
				break;
			case 3 :
				$result = "The camera doesn't make a bit of difference. All of them can record what you are seeing. But, you have to SEE.<br/><i>Ernst Haas (1921 - 1986)</i>";
				break;
			case 4 :
				$result = "We try to grab pieces of our lives as they speed past us. Photographs freeze those pieces and help us remember how we were. We don't know these lost people but if you look around, you'll find someone just like them.<br/><i>Gene McSweeney</i>";
				break;
			case 5 :
				$result = "Photographers deal in things which are continually vanishing and when they have vanished there is no contrivance on earth which can make them come back again.<br/><i>Henri Cartier Bresson (1908 - 2004)</i>";
				break;
			case 6 :
				$result = "Maybe because it's entirely an artist's eye, patience and skill that makes an image and not his tools.<br/><i>Ken Rockwell</i>";
				break;
			case 7 :
				$result = "No matter how advanced your camera you still need to be responsible for getting it to the right place at the right time and pointing it in the right direction to get the photo you want.<br/><i>Ken Rockwell</i>";
				break;
			case 8 :
				$result = "The camera's only job is to get out of the way of making photographs.<br/><i>Ken Rockwell</i>";
				break;
			case 9 :
				$result = "Photography, fortunately, to me has not only been a profession but also a contact between people - to understand human nature and record, if possible, the best in each individual.<br/><i>Nickolas Muray</i>";
				break;
			case 10 :
				$result = "Every portrait that is painted with feeling is a portrait of the artist, not of the sitter.<br/><i>Oscar Wilde (1854 - 1900)</i>";
				break;
			case 11 :
				$result = "Let the subject generate its own photographs. Become a camera.<br/><i>Minor White (1908 - 1976)</i>";
				break;
			case 12 :
				$result = "Keep it simple.<br/><i>Alfred Eisenstaedt (1898 - 1995)</i>";
				break;
			case 13 :
				$result = "Which of my photographs is my favorite? The one I'm going to take tomorrow.<br/><i>Imogen Cunningham (1883 - 1976)</i>";
				break;
			case 14 :
				$result = "I have to be as much diplomat as a photographer.<br/><i>Alfred Eisenstaedt (1898 - 1995)</i>";
				break;
			case 15 :
				$result = "I never photograph reality.<br/><i>Sarah Moon</i>";
				break;
			case 16 :
				$result = "My best work is often almost unconscious and occurs ahead of my ability to understand it.<br/><i>Sam Abell</i>";
				break;
			case 17 :
				$result = "The photographer is his own worst critic.<br/><i>Iain Mavin</i>";
				break;
			case 18 :
				$result = "No place is boring, if you've had a good night's sleep and have a pocket full of unexposed film.<br/><i>Robert Adams</i>";
				break;
			case 19 :
				$result = "A good snapshot stops a moment from running away.<br/><i>Eudora Welty</i>";
				break;
			case 20 :
				$result = "No photographer is as good as the simplest camera.<br/><i>Edward Steichen</i>";
				break;
			case 21 :
				$result = "There are no rules for good photographs, there are only good photographs.<br/><i>Ansel Adams</i>";
				break;
			case 22 :
				$result = "My portraits are more about me than they are about the people I photograph.<br/><i>Richard Avedon</i>";
				break;
			case 23 :
				$result = "I hate cameras.  They are so much more sure than I am about everything.<br/><i>John Steinbeck</i>";
				break;
			case 24 :
				$result = "All photos are accurate.  None of them is the truth.<br/><i>Richard Avedon</i>";
				break;
			case 25 :
				$result = "The camera cannot lie, but it can be an accessory to untruth.<br/><i>Harold Evans</i>";
				break;
			case 26 :
				$result = "I never question what to do, it tells me what to do.  The photographs make themselves with my help.<br/><i>Ruth Bernhard</i>";
				break;
			case 27 :
				$result = "The photograph itself doesn't interest me.  I want only to capture a minute part of reality.<br/><i>Henri Cartier Bresson</i>";
				break;
			case 28 :
				$result = "A photograph is like the recipe - a memory the finished dish.<br/><i>Carrie Latet</i>";
				break;
			case 29 :
				$result = "Photography deals exquisitely with appearances, but nothing is what it appears to be.<br/><i>Duane Michals</i>";
				break;
			case 30 :
				$result = "A snapshot steals life that it cannot return. A long exposure [creates] a form that never existed.<br/><i>Dieter Appelt</i>";
				break;
		}
		return $result;
	}
	
?>
