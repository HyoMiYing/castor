# CASTOR

CASTOR is a management system for monitoring and manipulating with ovens for carbon fiber parts.

## The problem

We need to remotley turn off/on the oven that is located in the factory.

## Web server

We connect to a web server via VPN. The website is a simple industrial one-page application.
There are visible information such as:
- number of connected ovens
- oven's temperature
- timestamp

We can also deactivate specific ovens.

### Setup

![Laptop, techbase microcomputer, and its charger](https://github.com/HyoMiYing/castor/blob/master/images/CastorSetup.jpg)

This is how I worked on the project: I was programming code on my laptop, then sent it to a techbase microcomputer (grey box)
(Orange box is microcomputer's charger)

Since there was no oven in development phase I used an API simulator written in C++.

### Functionality

The code I have worked on consists of two API functions:
1st function: Get oven data
2nd functioni: Set oven status (takes oven id and oven status (o or 1) parameters)

I also designed the UI myself

![Castor UI](https://github.com/HyoMiYing/castor/blob/master/images/CastorUI.jpg)

The web server's code was uploaded to a microcomputer, which I then accessed via browser (http://192.168.2.102/nx/)
