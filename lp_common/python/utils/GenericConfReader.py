#!/usr/bin/env python


# THIS SOFTWARE IS SUPPLIED WITHOUT WARRANTY OF ANY KIND, AND MAY BE COPIED,
# MODIFIED OR DISTRIBUTED IN ANY WAY, AS LONG AS THIS NOTICE AND ACKNOWLEDGEMENT
# OF AUTHORSHIP REMAIN.


'''\
ConfReader - configuration file reading class
             (c) 1998 Mark Nottingham <mnot@pobox.com>
             http://www.mnot.net/python/


This class offers a flexible configuration file reading interface. 

Introduction 

  Initialise with one argument- the name or location of the file.
  If it is not absolute, the program's directory will be searched for it. In
  either case, IOError will be raised if it cannot be read.


Configuration File Format

  The format of the configuration file can be set before parsing. Blank lines
  are always ignored. Whitespace at the start and end of any line is always
  ignored.
  
  Comment lines are also ignored; by default, the '#' character at the start
  of a line denotes a comment. This can be changed with the .config_comment
  attribute.

  A configuration line takes the format 
    name [separator] value 
  Whitespace between these elements is ignored. The separator defaults to
  '=', but can be changed with the .config_separator attribute. By default,
  the name is case sensative, but this can be turned off by setting 
  the .config_case_sensative attribute to 0. If this is done, all references
  in your script should be in lowercase.

Setting Configuration Defaults and other Parameters

  Parameters of config items can be specified by the set() function; the
  first argument is the name of the config (as in the file, a string),
  followed by four optional arguments:

  * default- the value returned if the config is not found in the file.
  * post- postprocessing function to use; can be lambda form or any function.
  * required- set to 1 if config is required.
  * list- set to 1 if config should always be returned as list. Multiple
    entries will be appended to the list if this is set; otherwise, the last
    entry will be used.

  The name of the config when set() should have any non-alphanumeric
  characters replaced by an underscore ('_').

  When referencing a function for postprocessing, just pass the name, without 
  the parenthesis().

  Defaults are set before postprocessing occurs; therefore, set defaults as
  they would be seen in the file.

Parsing

  Once parameters have been set, call the parse() method, which will apply
  them to the configs and make them available as attributes. Users of the 
  parse() method should be ready to catch:

  * ConfigMissingError- if a required config is missing
  * ConfigPostError- if any errors are raised by postprocessing
  * ConfigLineError- if there are any malformed lines
  
  The argument to the error raised will be a (section, name) tuple.

  For postprocessing errors, the .config_line_number attribute stores the last
  line of the file seen; this makes it possible to see where the error is
  in the file.

Accessing Configuration Data

  Once you parse() the file, you can access any configuration by looking at
  the corresponding attribute; for instance "foo boo" in the configuration
  file would show up as
    config.foo_boo
  if your instance is called config.

  If an attribute is accessed that isn't available in the configuration file,
  and a default hasn't been set, AttributeError will be raised.

Sections

  Optionally, the configuration file can be arranged in sections of data,
  which can be used to organise references to multiple resources. For
  example, a section could look like:
  
  [section_name]
  config = value
  ...

  section_name is the name of the section; it must be unique, and will have
  all whitespace and non-alphanumeric characters replaced by '_'.
  
  The section is ended when the parser begins another section, or hits the 
  end of the file.
  
  Each section is available as an attribute; configurations made in 
  it are available of attributes of that attribute. For instance, the section
  above would be available as:
  
  config.section_name  <-- the section itself
  config.section_name.config  <-- returns 'value'

  For attributes that are to be found in sections, use set_s() instead of 
  set(). 

  All sections available can be listed by calling get_sections(). An 
  individual section can be fetched with get_section().

  Defaults that filter down to all sections can be declared in a 
  [DEFAULT] section. The name used for this is configurable via the
  .config_default_section attribute.

  The delimiter for section heads defaults to '[]'; this can be changed to
  any two-character string (corresponding to the first and last
  non-whitespace characters on a line) by the config_section attribute.


Example

>>> try:
>>> 	config = ConfReader(filename)
>>> except IOError:							# can't read file
>>> 	print "can't read", filename
>>> config.set('one_conf_item', default='foo')		# default item
>>> config.set('another_item', post=int)			# return an integer
>>> import string
>>> config.set('yet_another', post=string.lower)	# return a lower string
>>> config.set('a_nother', post=string.split)		# return a list of words
>>> config.set('and_another', post=lambda a: a)		# custom postprocessing
>>> config.set('heres_one', list=1)					# return a list
>>> config.set('last_one', required=1)				# retquired config
>>> try:
>>> 	config.parse()								# parse the file
>>> except ConfigMissingError, why:
>>> 	print filename, "Missing config", why
>>> except ConfigPostError, why:
>>> 	print filename, "Postprocessing Error", why
>>> print config.one_conf_item						# access a config
'foo'
>>> print config.heres_one
['value', 'value', 'value']


yesno() function:
  yesno() can be used as a convenient postprocessing function to determine
  binary values. See the function for exact behavior. It will raise a
  ValueError if it cannot determine the truth of its input.


TODO
* alternate input methods (any object with a readline(), from array...)
* test() function
* multi-line configs
* configurable error handling
'''



__version__ = "0.60"

from types import *
from os import path
from string import strip, split, lower
import sys, re

illegal_chars = re.compile('\W')	# pattern of illegal characters in
									# attribute names

ConfigMissingError = 'required configuration missing'
ConfigPostError = 'postprocessing error'
ConfigLineError = 'malformed configuration line'


class ConfReader:
	''' configuration file reading class '''

	def __init__(self, file):
		''' slurp in the conf file '''

		self.config_separator = '='
		self.config_comment = '#'
		self.config_section = '[]'
		self.config_default_section = 'DEFAULT'
		self.config_root_section = 'ROOT'
		self.config_case_sensative = 1

		self.config_line_number = 0
		if not path.isabs(file):
			prog_name = sys.argv[0]
			prog_path = path.split(prog_name)[0]
			file = path.join(prog_path, file)
		conf = open(file, 'r')
		self.confs = conf.readlines()
		conf.close()

		self.__defaults = {}
		self.__section_defaults = {}
		self.__post_proc = {}
		self.__section_post_proc = {}
		self.__required = {}
		self.__section_required = {}
		self.__return_list = {}
		self.__section_return_list = {}
		self.__sections = []


	def __getattr__(self, attr):
		return getattr(self.__dict__[self.config_root_section], attr)

	
	def set(self, name, default=None, post=None, required=0, list=0):
		''' set parameters for a root-level configuration item '''
		self.__defaults[name] = default
		self.__post_proc[name] = post
		self.__required[name] = required
		self.__return_list[name] = list


	def set_s(self, name, default=None, post=None, required=0, list=0):
		''' set parameters for a section configuration item '''
		self.__section_defaults[name] = default
		self.__section_post_proc[name] = post
		self.__section_required[name] = required
		self.__section_return_list[name] = list


	def parse(self):
		''' Parse the stored configuration '''
		self.config_line_number = 0			# reset line counter
		current_section = self.config_root_section
		setattr(self, self.config_root_section, Section())
		setattr(self, self.config_default_section, Section())
		tmp_objs = {self.config_root_section: {}}

		### process line by line
		for line in self.confs:
			if not line:
				break
			line = strip(line)
			self.config_line_number = self.config_line_number + 1

			### ignore comments, blank lines
			if not line or line[0] == self.config_comment: 
				continue

			### look for section heads
			if line[0] == self.config_section[0] and \
			  line[-1] == self.config_section[-1]:
				current_section = re.sub(illegal_chars, '_', line[1:-1])
				if current_section != self.config_default_section:
					self.__sections.append(current_section)
				setattr(self, current_section, Section(
				  eval('self.' + self.config_default_section)))
				tmp_objs[current_section] = {}
			  	continue

			### split conf name/value	
			try:
				(conf_name, conf_value) = split(line, self.config_separator, 1)
			except ValueError:
				raise ConfigLineError, (current_section, line)
			conf_name = re.sub(illegal_chars, '_', strip(conf_name))
			if not self.config_case_sensative:
				conf_name = lower(conf_name)
			conf_value = strip(conf_value)
			
			### add to temp dictionary
			if tmp_objs[current_section].has_key(conf_name):
				tmp_objs[current_section][conf_name].append(conf_value)
			else:
				tmp_objs[current_section][conf_name] = [conf_value]


		### run through required, defaults, postprocess, list
		for section in self.get_sections() + [self.config_root_section]:
			tmp = tmp_objs[section]

			if section == self.config_root_section: 
				required = self.__required
				defaults = self.__defaults
				post_proc = self.__post_proc 
				return_list = self.__return_list
			else:
				required = self.__section_required
				defaults = self.__section_defaults
				post_proc = self.__section_post_proc
				return_list = self.__section_return_list

				### section defaults
				for name, value in tmp_objs[self.config_default_section].items():
					if not tmp.has_key(name):
						tmp[name] = value

			### required
			for name, required in required.items():
				if not tmp.has_key(name) and \
				  required:
					raise ConfigMissingError, (section, name)

			### set defaults
			for name, value in defaults.items():
				if not tmp.has_key(name):
					tmp[name] = [value]

			### postprocess
			for name, value in tmp.items():
				tr_func = post_proc.get(name, None)
				if tr_func is None: tr_func = lambda a:a
				try:
					tmp[name] = map(tr_func, value)
				except StandardError, why:
					raise ConfigPostError, (section, name)

			### list
			for name, value in tmp.items():
				if return_list.get(name, 0):
					if type(value) is not ListType:
						value = [value]
				else:
					if type(value) is ListType:
						value = value[-1]

				### populate attributes
				setattr(eval('self.' + section), name, value)


	def get_sections(self):
		''' Return a list of available sections. '''
		return self.__sections


	def get_section(self, section):
		''' Return a section. '''
		return getattr(self, section)



class Section:
	def __init__(self, default=None):
		self.__default = default

	def __getattr__(self, attr):
		if self.__default:
			return getattr(self.__default, attr)
		else:
			raise AttributeError, attr


def yesno(element):
	''' return 1 for true, 0 for false, or raise error '''

	e = lower(string.strip(element))	
	if e[0] == 't' or e == '1' or e[0] == 'y' or e == 'on':
		return 1
	elif e[0] == 'f' or e == '0' or e[0] == 'n' or e == 'off':
		return 0
	else:
		raise ValueError, "can't determine value of %s" % (element)
		

def test():
	''' haven't gotten around to this yet '''
	pass



if __name__ == '__main__':
	test()
